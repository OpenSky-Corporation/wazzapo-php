<?php
/**
 * Бот с кнопками: на входящее сообщение отвечает тремя кнопками, на нажатие кнопки отвечает текстом.
 *
 * Запуск: WAZZAPO_API_KEY=... WAZZAPO_WEBHOOK_SECRET=... php -S 0.0.0.0:8080 bot-buttons.php
 * Адрес http(s)://ваш-сервер:8080/ укажите вебхуком на событие message.incoming.
 */
declare(strict_types=1);

require __DIR__ . '/_autoload.php';

use Wazzapo\Exception\WazzapoException;
use Wazzapo\Exception\WebhookVerificationException;
use Wazzapo\Webhooks;
use Wazzapo\Wazzapo;

const ANSWERS = [
    'prices' => 'Стрижка 5 000 ₸, окрашивание от 15 000 ₸. Записаться можно кнопкой «Записаться».',
    'book' => 'Отлично! Напишите удобный день и время, администратор подтвердит запись.',
    'manager' => 'Позвали администратора, он ответит в этом чате в течение 10 минут.',
];

/** Повторная доставка того же события: отвечаем 200 и ничего не делаем. */
function seen(?string $deliveryId): bool
{
    if ($deliveryId === null) {
        return false;
    }
    $file = sys_get_temp_dir() . '/wazzapo-bot-deliveries.txt';
    $ids = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
    if (in_array($deliveryId, $ids, true)) {
        return true;
    }
    $ids[] = $deliveryId;
    file_put_contents($file, implode("\n", array_slice($ids, -1000)) . "\n", LOCK_EX);
    return false;
}

function reply(Wazzapo $client, array $data): array
{
    $message = $data['message'];
    $base = ['phone_number_id' => $data['phone_number_id'], 'to' => $message['from']];
    $pressed = $message['interactive_reply_id'] ?? null;
    if ($pressed !== null && isset(ANSWERS[$pressed])) {
        return $client->messages->send($base + ['type' => 'text', 'text' => ['body' => ANSWERS[$pressed]]]);
    }
    $name = $data['contact']['name'] ?? '';
    return $client->messages->send($base + [
        'type' => 'interactive',
        'interactive' => [
            'type' => 'button',
            'body' => ['text' => trim("Здравствуйте, {$name}! Чем помочь?")],
            'action' => ['buttons' => [
                ['type' => 'reply', 'reply' => ['id' => 'prices', 'title' => 'Цены']],
                ['type' => 'reply', 'reply' => ['id' => 'book', 'title' => 'Записаться']],
                ['type' => 'reply', 'reply' => ['id' => 'manager', 'title' => 'Администратор']],
            ]],
        ],
    ]);
}

$body = (string) file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];
header('Content-Type: application/json');
try {
    $event = Webhooks::verify($body, $headers, getenv('WAZZAPO_WEBHOOK_SECRET'));
} catch (WebhookVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    return;
}
if (seen(Webhooks::deliveryId($headers))) {
    echo '{"ok":true,"duplicate":true}';
    return;
}
$data = $event['data'];
// Только WhatsApp: у Telegram и чата на сайте свои правила кнопок
if ($event['event'] !== 'message.incoming' || ($data['channel'] ?? 'whatsapp') !== 'whatsapp') {
    echo '{"ok":true,"skipped":true}';
    return;
}
try {
    $sent = reply(new Wazzapo(), $data);
    echo json_encode(['ok' => true, 'message_id' => $sent['message_id']]);
} catch (WazzapoException $e) {
    // 200, чтобы Wazzapo не повторял доставку: ошибка отправки повтором вебхука не исправится
    error_log("Wazzapo: {$e->errorCode} {$e->getMessage()}");
    echo json_encode(['ok' => false, 'code' => $e->errorCode], JSON_UNESCAPED_UNICODE);
}
