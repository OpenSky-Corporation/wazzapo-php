<?php

declare(strict_types=1);

namespace Wazzapo;

use Wazzapo\Exception\WebhookVerificationException;
use Wazzapo\Generated\WebhookEvents;

/**
 * Проверка подписи вебхука v2: X-Wazzapo-Signature-V2 = "v2=" . HMAC-SHA256(secret, timestamp . "." . тело),
 * X-Wazzapo-Timestamp: unix-время отправки. X-Wazzapo-Delivery-Id одинаков во всех повторах одной доставки.
 */
final class Webhooks
{
    public const SIGNATURE_HEADER = 'X-Wazzapo-Signature-V2';
    public const TIMESTAMP_HEADER = 'X-Wazzapo-Timestamp';
    /** id доставки: тот же во всех повторах, по нему отсеивайте дубли */
    public const DELIVERY_ID_HEADER = 'X-Wazzapo-Delivery-Id';

    /**
     * Проверяет подпись и время и возвращает событие {event, timestamp, data}.
     *
     * @param string $body сырое тело запроса как пришло (file_get_contents('php://input')), не json_decode
     * @param array<string, string|list<string>> $headers заголовки запроса, регистр имён не важен (getallheaders())
     * @param string|false|null $secret секрет вебхука из POST /webhooks или rotate-secret;
     *   false и null (getenv() без переменной) и пустая строка дают ошибку, а не проверку пустым ключом
     * @param int $tolerance допустимое расхождение времени в секундах
     * @return array{event: string, timestamp: string, data: array<string, mixed>}
     * @throws WebhookVerificationException нет секрета или заголовков, подпись не сошлась, время вне допуска, тело не JSON
     */
    public static function verify(string $body, array $headers, string|false|null $secret, int $tolerance = 300, ?int $now = null): array
    {
        $secret = self::secret($secret);
        $lower = array_change_key_case($headers, CASE_LOWER);
        $signature = self::first($lower[strtolower(self::SIGNATURE_HEADER)] ?? null);
        $timestamp = self::first($lower[strtolower(self::TIMESTAMP_HEADER)] ?? null);
        if ($signature === null || $timestamp === null || !ctype_digit($timestamp)) {
            throw new WebhookVerificationException('Нет заголовков подписи X-Wazzapo-Signature-V2 и X-Wazzapo-Timestamp');
        }
        if (abs(($now ?? time()) - (int) $timestamp) > $tolerance) {
            throw new WebhookVerificationException('Метка времени вебхука вне допуска: возможно, запрос переотправлен');
        }
        $expected = self::signature($body, $secret, (int) $timestamp);
        $matched = false;
        // Несколько подписей через запятую (смена секрета): достаточно одной, сравниваем все за постоянное время
        foreach (explode(',', $signature) as $candidate) {
            $matched = hash_equals($expected, trim($candidate)) || $matched;
        }
        if (!$matched) {
            throw new WebhookVerificationException('Подпись вебхука не сошлась: проверьте секрет');
        }
        $event = json_decode($body, true);
        if (!is_array($event) || !isset($event['event'])) {
            throw new WebhookVerificationException('Тело вебхука не JSON события');
        }
        return $event;
    }

    /**
     * Заголовки подписи для тела: для своих тестов приёма вебхуков.
     *
     * @return array<string, string>
     * @throws WebhookVerificationException секрет пуст
     */
    public static function sign(string $body, string|false|null $secret, ?int $timestamp = null): array
    {
        $secret = self::secret($secret);
        $timestamp ??= time();
        return [
            self::TIMESTAMP_HEADER => (string) $timestamp,
            self::SIGNATURE_HEADER => self::signature($body, $secret, $timestamp),
        ];
    }

    /**
     * id доставки из заголовков или null: ключ для отсева повторов одного события.
     *
     * @param array<string, string|list<string>> $headers
     */
    public static function deliveryId(array $headers): ?string
    {
        $lower = array_change_key_case($headers, CASE_LOWER);
        return self::first($lower[strtolower(self::DELIVERY_ID_HEADER)] ?? null);
    }

    /** @return list<string> все события, на которые можно подписать вебхук */
    public static function events(): array
    {
        return array_keys(WebhookEvents::ALL);
    }

    private static function secret(string|false|null $secret): string
    {
        if (!is_string($secret) || trim($secret) === '') {
            throw new WebhookVerificationException('Секрет вебхука пуст: передайте secret из POST /webhooks (например, getenv(\'WAZZAPO_WEBHOOK_SECRET\'))');
        }
        return $secret;
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
