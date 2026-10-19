<?php
/**
 * Приёмник всех событий Wazzapo: проверяет подпись, сразу отвечает 200 и разбирает событие по типу.
 *
 * Запуск: WAZZAPO_WEBHOOK_SECRET=... php -S 0.0.0.0:8082 webhooks-receiver.php
 */
declare(strict_types=1);

require __DIR__ . '/_autoload.php';

use Wazzapo\Exception\WebhookVerificationException;
use Wazzapo\Webhooks;

$body = (string) file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];
try {
    $event = Webhooks::verify($body, $headers, getenv('WAZZAPO_WEBHOOK_SECRET'));
} catch (WebhookVerificationException $e) {
    http_response_code(400);
    echo $e->getMessage();
    return;
}

// Отвечаем сразу: долгая обработка до ответа приводит к повторной доставке
http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$data = $event['data'];
$line = match ($event['event']) {
    'deal.stage_changed' => sprintf(
        'Сделка %s: этап «%s» → «%s»',
        $data['deal']['number'] ?? $data['deal']['id'] ?? '?',
        $data['from_stage']['name'] ?? '?',
        $data['to_stage']['name'] ?? '?',
    ),
    'message.status' => sprintf('Сообщение %s: %s', $data['message_id'] ?? '?', $data['status'] ?? '?'),
    'message.incoming' => sprintf('Новое сообщение от %s: %s', $data['message']['from'] ?? '?', $data['message']['text'] ?? ''),
    'deal.created' => sprintf('Новая сделка %s', $data['deal']['number'] ?? $data['deal']['id'] ?? '?'),
    default => 'Событие ' . $event['event'],
};
$delivery = Webhooks::deliveryId($headers) ?? '-';
error_log("[wazzapo {$delivery}] {$line}");
