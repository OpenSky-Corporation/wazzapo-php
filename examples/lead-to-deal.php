<?php
/**
 * Заявка с сайта в сделку: форма шлёт POST name, phone, comment, пример создаёт сделку
 * и отправляет клиенту приветственный шаблон WhatsApp.
 *
 * Запуск: WAZZAPO_API_KEY=... WAZZAPO_PHONE_NUMBER_ID=... WAZZAPO_TEMPLATE_NAME=... php -S 0.0.0.0:8081 lead-to-deal.php
 * Форма: <form method="post" action="http://ваш-сервер:8081/"> с полями name, phone, comment.
 */
declare(strict_types=1);

require __DIR__ . '/_autoload.php';

use Wazzapo\Exception\ConflictException;
use Wazzapo\Exception\WazzapoException;
use Wazzapo\Wazzapo;

function env(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

/**
 * Сделка без дублей: повтор той же заявки (двойной клик, перезагрузка страницы) вернёт уже созданную.
 *
 * @return array{0: array<string, mixed>, 1: bool} сделка и признак «создана сейчас»
 */
function dealFor(Wazzapo $client, array $lead): array
{
    $pipelineId = env('WAZZAPO_PIPELINE_ID') ?: $client->pipelines->list()['data'][0]['id'];
    // Свой id заявки из формы или CRM; без него считаем по телефону и тексту, чтобы повтор совпал
    $externalId = $lead['lead_id'] ?: 'site-' . sha1($lead['phone'] . '|' . $lead['comment']);
    try {
        $deal = $client->deals->create([
            'pipeline_id' => $pipelineId,
            'title' => 'Заявка с сайта: ' . ($lead['name'] ?: $lead['phone']),
            // Контакт по телефону: Wazzapo найдёт его или создаст
            'contact' => ['phone' => $lead['phone'], 'name' => $lead['name'] ?: null],
            'external_id' => $externalId,
        ]);
        return [$deal, true];
    } catch (ConflictException $e) {
        if ($e->errorCode !== 'deal_exists') {
            throw $e;
        }
        return [$client->deals->get((string) $e->details['deal_id']), false];
    }
}

function greet(Wazzapo $client, array $lead): array
{
    $template = ['name' => env('WAZZAPO_TEMPLATE_NAME'), 'language' => ['code' => env('WAZZAPO_TEMPLATE_LANG', 'ru')]];
    // Шаблон с {{1}} в тексте: подставляем имя клиента
    if (env('WAZZAPO_TEMPLATE_NAME_PARAM') === '1') {
        $template['components'] = [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $lead['name'] ?: 'клиент']]]];
    }
    return $client->messages->send([
        'phone_number_id' => env('WAZZAPO_PHONE_NUMBER_ID'),
        'to' => $lead['phone'],
        'type' => 'template',
        'template' => $template,
    ]);
}

header('Content-Type: application/json');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo '{"error":"Нужен POST с полями name, phone, comment"}';
    return;
}
$input = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json')
    ? (array) json_decode((string) file_get_contents('php://input'), true)
    : $_POST;
$lead = [
    'name' => trim((string) ($input['name'] ?? '')),
    'phone' => preg_replace('/\D+/', '', (string) ($input['phone'] ?? '')),
    'comment' => trim((string) ($input['comment'] ?? '')),
    'lead_id' => trim((string) ($input['lead_id'] ?? '')),
];
if (strlen($lead['phone']) < 10) {
    http_response_code(422);
    echo '{"error":"Укажите телефон"}';
    return;
}
try {
    $client = new Wazzapo();
    [$deal, $created] = dealFor($client, $lead);
    $out = ['ok' => true, 'deal_id' => $deal['id'], 'deal_number' => $deal['number'], 'duplicate' => !$created];
    // Приветствие только новой заявке: повтор формы не шлёт клиенту второй шаблон
    if ($created && env('WAZZAPO_TEMPLATE_NAME') !== '') {
        $out['message_id'] = greet($client, $lead)['message_id'];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (WazzapoException $e) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'code' => $e->errorCode, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
