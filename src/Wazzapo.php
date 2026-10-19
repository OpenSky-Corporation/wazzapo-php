<?php

declare(strict_types=1);

namespace Wazzapo;

use Wazzapo\Exception\WazzapoException;
use Wazzapo\Generated\Resources;
use Wazzapo\Transport\CurlTransport;
use Wazzapo\Transport\Transport;

/**
 * Клиент API Wazzapo v2.
 *
 *     $client = new Wazzapo\Wazzapo(['api_key' => 'wz_live_…']);
 *     $numbers = $client->phoneNumbers->list();
 *
 * Ключ из раздела «API» кабинета или из переменной окружения WAZZAPO_API_KEY.
 * Ключ wz_test_… ничего не отправляет и не меняет: для отладки.
 */
final class Wazzapo
{
    use Resources;

    public const DEFAULT_BASE_URL = 'https://wazzapo.kz';

    public readonly HttpClient $http;
    private readonly string $apiKey;

    /**
     * @param array{api_key?: string, base_url?: string, timeout?: float, max_retries?: int, transport?: Transport, sleep?: callable(float): void} $options
     *   timeout в секундах (по умолчанию 30), max_retries: сколько раз повторить при 429, 5xx и сбое сети (по умолчанию 2)
     */
    public function __construct(array $options = [])
    {
        $key = $options['api_key'] ?? (getenv('WAZZAPO_API_KEY') ?: '');
        if ($key === '') {
            throw new WazzapoException('Нет API-ключа: передайте api_key или задайте WAZZAPO_API_KEY');
        }
        $this->apiKey = $key;
        $this->http = new HttpClient(
            $key,
            $options['base_url'] ?? (getenv('WAZZAPO_BASE_URL') ?: self::DEFAULT_BASE_URL),
            (float) ($options['timeout'] ?? 30.0),
            (int) ($options['max_retries'] ?? 2),
            $options['transport'] ?? new CurlTransport(),
            $options['sleep'] ?? null,
        );
        $this->initResources($this->http);
    }

    /** Тестовый ключ wz_test_: запросы ничего не отправляют в WhatsApp и не меняют данные. */
    public function isTest(): bool
    {
        return str_starts_with($this->apiKey, 'wz_test_');
    }

    /** @return array<string, mixed> var_dump и print_r не покажут ключ целиком */
    public function __debugInfo(): array
    {
        return ['api_key' => $this->http->maskedKey(), 'test' => $this->isTest()];
    }
}
