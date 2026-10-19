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

    /**
     * @param array{api_key?: string, base_url?: string, timeout?: float, max_retries?: int, transport?: Transport, sleep?: callable(float): void} $options
     *   timeout в секундах (по умолчанию 30), max_retries: сколько раз повторить при 429, 5xx и сбое сети (по умолчанию 2)
     */
    public function __construct(array $options = [])
    {
        // getenv() без переменной даёт false: такой ключ считаем отсутствующим, а не падаем на типе
        $key = $options['api_key'] ?? null;
        if (!is_string($key) || $key === '') {
            $env = getenv('WAZZAPO_API_KEY');
            $key = is_string($env) ? $env : '';
        }
        $key = trim($key);
        if ($key === '') {
            throw new WazzapoException('Нет API-ключа: передайте api_key или задайте WAZZAPO_API_KEY', null, 'MISSING_API_KEY');
        }
        $this->http = new HttpClient(
            $key,
            self::baseUrl($options['base_url'] ?? null),
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
        return $this->http->isTest();
    }

    /** @return array<string, mixed> serialize не сохранит ключ */
    public function __serialize(): array
    {
        throw new \LogicException('Клиент Wazzapo нельзя сериализовать: создайте новый с ключом');
    }

    private static function baseUrl(mixed $option): string
    {
        if (is_string($option) && $option !== '') {
            return $option;
        }
        $env = getenv('WAZZAPO_BASE_URL');
        return is_string($env) && $env !== '' ? $env : self::DEFAULT_BASE_URL;
    }

    /** @return array<string, mixed> var_dump и print_r не покажут ключ целиком */
    public function __debugInfo(): array
    {
        return ['api_key' => $this->http->maskedKey(), 'test' => $this->isTest()];
    }
}
