<?php

declare(strict_types=1);

namespace Wazzapo;

use Wazzapo\Exception\WazzapoException;
use Wazzapo\Generated\WebhookEvents;

/**
 * Проверка подписи вебхука v2: X-Wazzapo-Signature-V2 = "v2=" . HMAC-SHA256(secret, timestamp . "." . тело),
 * X-Wazzapo-Timestamp: unix-время отправки.
 */
final class Webhooks
{
    public const SIGNATURE_HEADER = 'X-Wazzapo-Signature-V2';
    public const TIMESTAMP_HEADER = 'X-Wazzapo-Timestamp';

    /**
     * Проверяет подпись и время и возвращает событие {event, timestamp, data}.
     *
     * @param string $body сырое тело запроса как пришло (file_get_contents('php://input')), не json_decode
     * @param array<string, string|list<string>> $headers заголовки запроса, регистр имён не важен (getallheaders())
     * @param string $secret секрет вебхука из POST /webhooks или rotate-secret
     * @param int $tolerance допустимое расхождение времени в секундах
     * @return array{event: string, timestamp: string, data: array<string, mixed>}
     * @throws WazzapoException подпись не сошлась, время вне допуска или тело не JSON
     */
    public static function verify(string $body, array $headers, string $secret, int $tolerance = 300, ?int $now = null): array
    {
        $lower = array_change_key_case($headers, CASE_LOWER);
        $signature = self::first($lower[strtolower(self::SIGNATURE_HEADER)] ?? null);
        $timestamp = self::first($lower[strtolower(self::TIMESTAMP_HEADER)] ?? null);
        if ($signature === null || $timestamp === null || !ctype_digit($timestamp)) {
            throw new WazzapoException('Нет заголовков подписи X-Wazzapo-Signature-V2 и X-Wazzapo-Timestamp');
        }
        if (abs(($now ?? time()) - (int) $timestamp) > $tolerance) {
            throw new WazzapoException('Метка времени вебхука вне допуска: возможно, запрос переотправлен');
        }
        $expected = self::signature($body, $secret, (int) $timestamp);
        if (!hash_equals($expected, $signature)) {
            throw new WazzapoException('Подпись вебхука не сошлась: проверьте секрет');
        }
        $event = json_decode($body, true);
        if (!is_array($event) || !isset($event['event'])) {
            throw new WazzapoException('Тело вебхука не JSON события');
        }
        return $event;
    }

    /**
     * Заголовки подписи для тела: для своих тестов приёма вебхуков.
     *
     * @return array<string, string>
     */
    public static function sign(string $body, string $secret, ?int $timestamp = null): array
    {
        $timestamp ??= time();
        return [
            self::TIMESTAMP_HEADER => (string) $timestamp,
            self::SIGNATURE_HEADER => self::signature($body, $secret, $timestamp),
        ];
    }

    /** @return list<string> все события, на которые можно подписать вебхук */
    public static function events(): array
    {
        return array_keys(WebhookEvents::ALL);
    }

    private static function signature(string $body, string $secret, int $timestamp): string
    {
        return 'v2=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    /** @param string|list<string>|null $value */
    private static function first(string|array|null $value): ?string
    {
        return is_array($value) ? ($value[0] ?? null) : $value;
    }
}
