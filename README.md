# Wazzapo PHP SDK

Библиотека для [API Wazzapo v2](https://wazzapo.kz/api/v2/external/docs): отправка сообщений и шаблонов
WhatsApp, переписки, контакты, сделки, задачи, вебхуки. PHP 8.1 и новее, без внешних зависимостей
(нужны расширения curl и json). Лицензия MIT.

Библиотека сама повторяет запрос при 429, 5xx и сбое сети, ставит `Idempotency-Key` на каждый POST
(сообщение не уйдёт дважды), превращает ошибки в исключения по коду и проходит списки по страницам.

## Установка

```bash
composer require wazzapo/wazzapo-php
```

Ключ возьмите в кабинете, раздел «API». Для отладки создайте тестовый ключ `wz_test_…`: с ним
запросы ничего не отправляют в WhatsApp и не меняют данные, а ответы такие же, как у настоящего.

## Первое сообщение за 5 минут

```php
<?php
require 'vendor/autoload.php';

$client = new Wazzapo\Wazzapo(['api_key' => getenv('WAZZAPO_API_KEY')]);

// 1. Номер, с которого отправлять
$number = $client->phoneNumbers->list(['channel' => 'whatsapp'])['data'][0];
$phoneNumberId = $number['phone_number_id'];

// 2. Текст: работает, если клиент писал вам за последние 24 часа
$sent = $client->messages->send([
    'phone_number_id' => $phoneNumberId,
    'to' => '77071234567',
    'type' => 'text',
    'text' => ['body' => 'Айгуль, здравствуйте! Запись на 25 октября в 15:00 подтверждена.'],
]);
echo $sent['message_id'], PHP_EOL;

// 3. Первое сообщение клиенту или после 24 часов: только одобренный шаблон
$client->messages->send([
    'phone_number_id' => $phoneNumberId,
    'to' => '77071234567',
    'type' => 'template',
    'template' => [
        'name' => 'zapis_napominanie',
        'language' => ['code' => 'ru'],
        'components' => [[
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => 'Айгуль'],
                ['type' => 'text', 'text' => '25 октября, 15:00'],
            ],
        ]],
    ],
]);
```

Одобренные шаблоны номера: `$client->templates->list($phoneNumberId, ['status' => 'APPROVED'])`.

Ресурсы клиента: `phoneNumbers`, `messages`, `templates`, `media`, `contacts`, `conversations`,
`pipelines`, `deals`, `tasks`, `team`, `webhooks`, `webhookEventTypes`. У каждого метода описание по-русски
с полями запроса: редактор покажет его в подсказке.

## Ошибки

Все исключения наследуют `Wazzapo\Exception\WazzapoException`. У ответа сервера есть `status`,
`errorCode` (машинный код, на него ориентируйтесь), `details` и `requestId` (сообщите его в поддержку).

```php
use Wazzapo\Exception;

try {
    $client->messages->send($message);
} catch (Exception\OutsideWindowException $e) {
    // прошло больше 24 часов: отправьте шаблон
} catch (Exception\UsageLimitException $e) {
    // лимит тарифа: $e->details['limit'], $e->details['upgrade_url']
} catch (Exception\ValidationException $e) {
    foreach ($e->fields() as $field) {
        echo $field['field'], ': ', $field['message'], PHP_EOL;
    }
} catch (Exception\RateLimitException $e) {
    sleep($e->retryAfter() ?? 1);
} catch (Exception\WazzapoException $e) {
    error_log("{$e->errorCode}: {$e->getMessage()} (request_id {$e->requestId})");
}
```

| Класс | Когда |
|---|---|
| `BadRequestException` | 400, неверный запрос |
| `AuthenticationException` | 401, ключ неверный |
| `PaymentRequiredException` | 402, тариф не оплачен |
| `PermissionDeniedException`, `UsageLimitException` | 403, нет права у ключа или исчерпан лимит |
| `NotFoundException` | 404 |
| `ConflictException` | 409, объект уже есть |
| `UnprocessableEntityException` и наследники `ValidationException`, `OutsideWindowException`, `TemplateException` | 422 |
| `RateLimitException` | 429 после всех повторов |
| `ServerException` | 5xx после всех повторов |
| `ConnectionException` (`errorCode` CONNECTION_ERROR), `TimeoutException` (TIMEOUT) | нет ответа |
| `WebhookVerificationException` | вебхук не прошёл проверку, см. «Вебхуки» |

Если ответ пришёл не от API (например, страница прокси с 502), `errorCode` берётся по статусу:
`BAD_GATEWAY`, `NOT_FOUND` и т.д., пустым он не бывает. `$e->errorCode`, а не `getCode()`: у исключений PHP
`getCode()` всегда целое число.

Полный список кодов: `Wazzapo\Generated\ErrorCodes::STATUS` и раздел «Коды ошибок» в описании API.

## Списки

`list()` возвращает одну страницу `{data, next_cursor, has_more}`. `listAll()` проходит все страницы:

```php
foreach ($client->contacts->listAll(['tag' => 'vip']) as $contact) {
    echo $contact['name'], PHP_EOL;
}
```

## Вебхуки

Подпишитесь: `$client->webhooks->create(['url' => 'https://example.kz/wazzapo', 'event_types' => ['message.incoming']])`.
В ответе `secret`: сохраните его. Приём на чистом PHP:

```php
<?php
require 'vendor/autoload.php';

$body = file_get_contents('php://input'); // сырое тело, до json_decode
$headers = getallheaders();
try {
    $event = Wazzapo\Webhooks::verify($body, $headers, getenv('WAZZAPO_WEBHOOK_SECRET'));
} catch (Wazzapo\Exception\WebhookVerificationException $e) {
    http_response_code(400);
    exit;
}

// Wazzapo повторяет доставку, если не получил 200: один и тот же X-Wazzapo-Delivery-Id во всех повторах
$deliveryId = Wazzapo\Webhooks::deliveryId($headers);
if ($deliveryId !== null && already_processed($deliveryId)) { // ваша проверка, например по таблице
    http_response_code(200);
    exit;
}

if ($event['event'] === 'message.incoming') {
    // $event['data']: кто написал и что
}
http_response_code(200);
```

`verify` сверяет подпись `X-Wazzapo-Signature-V2` и время `X-Wazzapo-Timestamp` (допуск 5 минут).
Если секрет не задан (`getenv` вернул false) или пуст, `verify` сразу бросает `WebhookVerificationException`:
событие без настоящего секрета не пройдёт. В заголовке подписи может быть несколько значений через запятую
(после смены секрета), достаточно совпадения одного. Отсеивайте дубли по `Webhooks::deliveryId()`
(заголовок `X-Wazzapo-Delivery-Id`).
Отвечайте 200 быстро, тяжёлую работу делайте после ответа. Все события: `Wazzapo\Webhooks::events()`.

## Файлы

```php
$mediaId = $client->media->upload($phoneNumberId, '/path/schet_1024.pdf')['media_id'];
$client->messages->send(['phone_number_id' => $phoneNumberId, 'to' => '77071234567', 'type' => 'document',
    'document' => ['id' => $mediaId, 'filename' => 'schet_1024.pdf']]);

$bytes = $client->media->download($mediaIdFromWebhook, $phoneNumberId);
```

Путь к несуществующему файлу даёт ошибку `FILE_NOT_FOUND` сразу, запрос не уходит.

## Примеры

Готовые примеры без фреймворков в папке [examples](https://github.com/OpenSky-Corporation/wazzapo-php/tree/main/examples):

- [bot-buttons.php](https://github.com/OpenSky-Corporation/wazzapo-php/tree/main/examples/bot-buttons.php): бот с кнопками отвечает на входящее сообщение и на нажатие кнопки;
- [lead-to-deal.php](https://github.com/OpenSky-Corporation/wazzapo-php/tree/main/examples/lead-to-deal.php): заявка с формы сайта становится сделкой без дублей, клиент получает приветственный шаблон;
- [webhooks-receiver.php](https://github.com/OpenSky-Corporation/wazzapo-php/tree/main/examples/webhooks-receiver.php): приёмник всех событий с проверкой подписи.

Там же описано, как проверить их на тестовом ключе и подписанным тестовым событием.

## Настройки

```php
new Wazzapo\Wazzapo([
    'api_key' => 'wz_live_…',  // или переменная WAZZAPO_API_KEY
    'timeout' => 30,            // секунд на запрос
    'max_retries' => 2,         // повторов при 429, 5xx и сбое сети
]);
```

Свой `Idempotency-Key` (например, id заявки), таймаут или заголовок на один вызов:
`$client->messages->send($message, ['idempotency_key' => 'order-1024', 'timeout' => 60])`.

`var_dump`, `print_r` и `var_export` не покажут ключ, `serialize($client)` запрещён.
`$client->isTest()` скажет, тестовый ли ключ. Без ключа конструктор сразу бросает понятную ошибку
`MISSING_API_KEY`.

Если сервер в списке вернёт тот же `next_cursor` повторно, `listAll()` остановится с ошибкой
`PAGINATION_LOOP`, а не будет крутиться бесконечно. `Retry-After` понимается и числом секунд, и датой.

## Разработка

Код в `src/Generated` создаёт `sdk/codegen/php.py` из `sdk/openapi.json`; руками не правьте:
`cd sdk && python3 -m codegen php`. Тесты: `composer install && vendor/bin/phpunit`,
сквозные с переменной `WAZZAPO_TEST_API_KEY=wz_test_…`.
