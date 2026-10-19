<?php

declare(strict_types=1);

namespace Wazzapo;

use Wazzapo\Exception\ConnectionException;
use Wazzapo\Exception\ErrorFactory;
use Wazzapo\Exception\WazzapoException;
use Wazzapo\Transport\Request;
use Wazzapo\Transport\Response;
use Wazzapo\Transport\Transport;

/**
 * HTTP-часть SDK: заголовки, повторы с задержкой, Idempotency-Key, разбор ошибок, пагинация.
 * Ресурсы ($client->messages и другие) вызывают её, напрямую она обычно не нужна.
 */
final class HttpClient
{
    public const VERSION = '0.1.0';
    private const PREFIX = '/api/v2/external';
    private const RETRY_STATUSES = [429, 500, 502, 503, 504];
    private const MAX_RETRY_AFTER = 60;

    /** @var callable(float): void */
    private $sleep;
    /** @var callable(): float случайное число от 0 до 1 */
    private $random;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
        private readonly Transport $transport,
        ?callable $sleep = null,
        ?callable $random = null,
    ) {
        $this->sleep = $sleep ?? static function (float $seconds): void {
            usleep((int) ($seconds * 1_000_000));
        };
        $this->random = $random ?? static fn (): float => mt_rand() / mt_getrandmax();
    }

    /** @return array<string, mixed> var_dump не покажет ключ целиком */
    public function __debugInfo(): array
    {
        return ['api_key' => $this->maskedKey(), 'base_url' => $this->baseUrl, 'timeout' => $this->timeout, 'max_retries' => $this->maxRetries];
    }

    /** Ключ для логов: первые 8 знаков и многоточие. */
    public function maskedKey(): string
    {
        return substr($this->apiKey, 0, 8) . '…';
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @param 'json'|'multipart'|'binary' $kind
     * @return array<string, mixed>|string|null
     * @throws WazzapoException
     */
    public function request(string $method, string $path, array $query, ?array $body, array $options, string $kind): array|string|null
    {
        $headers = [
            'X-API-Key' => $this->apiKey,
            'User-Agent' => 'wazzapo-sdk-php/' . self::VERSION . ' php/' . PHP_VERSION,
            'Accept' => $kind === 'binary' ? '*/*' : 'application/json',
        ];
        $payload = null;
        if ($kind === 'multipart') {
            $payload = array_map(self::file(...), $body ?? []);
        } elseif ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $payload = json_encode($body === [] ? new \stdClass() : $body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        if ($method === 'POST') {
            // Один ключ на все повторы: сервер не отправит сообщение дважды
            $headers['Idempotency-Key'] = $options['idempotency_key'] ?? self::uuid4();
        }
        $headers = ($options['headers'] ?? []) + $headers;
        $request = new Request($method, $this->url($path, $query), $headers, $payload, (float) ($options['timeout'] ?? $this->timeout));
        $response = $this->send($request);
        if ($kind === 'binary') {
            return $response->body;
        }
        if ($response->status === 204 || $response->body === '') {
            return null;
        }
        $data = json_decode($response->body, true);
        if (!is_array($data)) {
            throw new WazzapoException('Сервер вернул не JSON', $response->status, null, null, $response->header('x-request-id'), $response->headers);
        }
        return $data;
    }

    /**
     * Все элементы всех страниц: идёт по next_cursor, пока has_more.
     *
     * @param array<string, mixed> $query
     * @param array{timeout?: float, headers?: array<string, string>} $options
     * @return \Generator<int, array<string, mixed>>
     */
    public function paginate(string $path, array $query, array $options): \Generator
    {
        while (true) {
            $page = $this->request('GET', $path, $query, null, $options, 'json') ?? [];
            foreach ($page['data'] ?? [] as $item) {
                yield $item;
            }
            $cursor = $page['next_cursor'] ?? null;
            if (empty($page['has_more']) || !is_string($cursor) || $cursor === '') {
                return;
            }
            $query['cursor'] = $cursor;
        }
    }

    private function send(Request $request): Response
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                $response = $this->transport->send($request);
            } catch (ConnectionException $e) {
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }
                ($this->sleep)($this->delay($attempt, null));
                continue;
            }
            if ($response->status < 400) {
                return $response;
            }
            if ($attempt < $this->maxRetries && in_array($response->status, self::RETRY_STATUSES, true)) {
                ($this->sleep)($this->delay($attempt, $response->header('retry-after')));
                continue;
            }
            throw ErrorFactory::fromResponse($response);
        }
    }

    /** Задержка перед повтором: Retry-After сервера или 0.5 × 2^n с (до 8 с) ± 25 %. */
    private function delay(int $attempt, ?string $retryAfter): float
    {
        if ($retryAfter !== null && is_numeric($retryAfter)) {
            return (float) min(max((int) $retryAfter, 0), self::MAX_RETRY_AFTER);
        }
        $base = min(0.5 * (2 ** $attempt), 8.0);
        return $base * (0.75 + 0.5 * ($this->random)());
    }

    /** @param array<string, mixed> $query */
    private function url(string $path, array $query): string
    {
        $query = array_filter($query, static fn ($v) => $v !== null);
        foreach ($query as $k => $v) {
            if (is_bool($v)) {
                $query[$k] = $v ? 'true' : 'false';
            }
        }
        $qs = $query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '';
        return rtrim($this->baseUrl, '/') . self::PREFIX . $path . $qs;
    }

    private static function file(mixed $value): mixed
    {
        if (is_string($value) && is_file($value)) {
            $type = function_exists('mime_content_type') ? (mime_content_type($value) ?: 'application/octet-stream') : 'application/octet-stream';
            return new \CURLFile($value, $type, basename($value));
        }
        return $value;
    }

    private static function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
