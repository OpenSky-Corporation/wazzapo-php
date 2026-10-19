# Примеры wazzapo/wazzapo-php

Три готовых примера на чистом PHP 8.1+, без фреймворков: запускаются встроенным сервером `php -S`.

| Файл | Что делает |
|---|---|
| `bot-buttons.php` | Бот с кнопками. На входящее сообщение отвечает тремя кнопками («Цены», «Записаться», «Администратор»), на нажатие кнопки отвечает текстом |
| `lead-to-deal.php` | Заявка с формы сайта (`name`, `phone`, `comment`) становится сделкой, клиент получает приветственный шаблон WhatsApp. Повтор той же заявки дубль не создаёт |
| `webhooks-receiver.php` | Приёмник всех событий: проверяет подпись, сразу отвечает 200 и пишет в журнал смену этапа сделки и статусы сообщений |

## Установка

В своём проекте:

```bash
composer require wazzapo/wazzapo-php
```

и в начале файла `require 'vendor/autoload.php';` вместо `_autoload.php` из примеров.

## Переменные окружения

| Переменная | Где взять | Нужна для |
|---|---|---|
| `WAZZAPO_API_KEY` | Кабинет, раздел «API». Для проверок ключ `wz_test_…`: он ничего не отправляет | всех, кроме приёмника |
| `WAZZAPO_WEBHOOK_SECRET` | Ответ при создании вебхука (`$client->webhooks->create(...)`) или в кабинете | `bot-buttons`, `webhooks-receiver` |
| `WAZZAPO_PHONE_NUMBER_ID` | `phone_number_id` из `$client->phoneNumbers->list(['channel' => 'whatsapp'])` | `lead-to-deal` |
| `WAZZAPO_TEMPLATE_NAME`, `WAZZAPO_TEMPLATE_LANG` | Одобренный шаблон из `$client->templates->list($phoneNumberId, ['status' => 'APPROVED'])`, язык по умолчанию `ru` | `lead-to-deal` |
| `WAZZAPO_TEMPLATE_NAME_PARAM` | `1`, если в тексте шаблона есть `{{1}}`: подставим имя клиента | `lead-to-deal` |
| `WAZZAPO_PIPELINE_ID` | id воронки из `$client->pipelines->list()`; без неё первая воронка | `lead-to-deal` |

## Запуск

```bash
cd examples
WAZZAPO_API_KEY=wz_test_... WAZZAPO_WEBHOOK_SECRET=... php -S 0.0.0.0:8080 bot-buttons.php
WAZZAPO_API_KEY=wz_test_... WAZZAPO_PHONE_NUMBER_ID=... WAZZAPO_TEMPLATE_NAME=... php -S 0.0.0.0:8081 lead-to-deal.php
WAZZAPO_WEBHOOK_SECRET=... php -S 0.0.0.0:8082 webhooks-receiver.php
```

Адрес бота и приёмника укажите вебхуком в кабинете или методом `$client->webhooks->create(['url' => 'https://ваш-сайт/...', 'event_types' => ['message.incoming']])`. Wazzapo шлёт вебхуки только на https: для своего компьютера подойдёт туннель (например, ngrok или cloudflared).

## Проверка без настоящих клиентов

С тестовым ключом `wz_test_…` запросы проходят все проверки (номер, шаблон, повтор запроса), но ничего не уходит в WhatsApp и не меняется в данных: в ответе `wamid.SANDBOX…` и `sandbox_…`.

Событие, как его пришлёт Wazzapo, можно подписать самим и отправить curl:

```bash
export WAZZAPO_WEBHOOK_SECRET=test_secret
BODY='{"event":"message.incoming","timestamp":"2026-10-19T12:00:00Z","data":{"channel":"whatsapp","phone_number_id":"ВАШ_PHONE_NUMBER_ID","contact":{"name":"Айгерим","phone":"77071234567"},"message":{"from":"77071234567","type":"text","text":"Здравствуйте","interactive_reply_id":null}}}'
php -r 'require "vendor/autoload.php"; foreach (Wazzapo\Webhooks::sign($argv[1], getenv("WAZZAPO_WEBHOOK_SECRET")) as $k => $v) echo "-H\n$k: $v\n";' "$BODY" > /tmp/h.txt
mapfile -t H < /tmp/h.txt
curl -X POST "${H[@]}" -H "X-Wazzapo-Delivery-Id: test-1" --data-binary "$BODY" http://127.0.0.1:8080/
```

Ответ `{"ok":true,"message_id":"wamid.SANDBOX…"}`. Тот же запрос второй раз даст `"duplicate":true`: повтор доставки по `X-Wazzapo-Delivery-Id` отсеивается. Нажатие кнопки: в `message.interactive_reply_id` поставьте `"prices"`.

Заявку проверить проще всего так:

```bash
curl -X POST -d "name=Айгерим&phone=+7 707 123 45 67&comment=Хочу на замер" http://127.0.0.1:8081/
```

## Что важно в своём коде

- Подпись проверяйте по сырому телу запроса (`file_get_contents('php://input')`), до `json_decode`.
- Отвечайте на вебхук быстро: долгую обработку делайте после ответа (`fastcgi_finish_request()` в PHP-FPM) или в очереди.
- Отсев повторов в примере хранит id доставок во временном файле. В работе храните их в базе или Redis.
- Чтобы заявка не стала двумя сделками, передавайте свой `external_id`: повтор вернёт ошибку `deal_exists` с id уже созданной сделки, пример её и отдаёт.
