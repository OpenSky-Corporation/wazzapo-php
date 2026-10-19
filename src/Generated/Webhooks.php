<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы webhooks: webhooks.create, webhooks.delete, webhooks.get, webhooks.list, webhooks.list_deliveries, webhooks.retry_delivery, webhooks.rotate_secret, webhooks.test, webhooks.update. */
final class Webhooks
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Подписаться на события
     *
     * Подписывает ваш адрес https на события: Wazzapo шлёт на него POST с JSON.
     *
     * Ответ содержит `secret`: он показывается один раз, сохраните его. Ключ `wz_test_…` вебхук не создаёт, но
     * отдаёт ответ того же вида.
     *
     * Ошибки: 422 `invalid_event_types` (список: GET /webhook-event-types), 422 адрес не https или внутренний, 403
     * `USAGE_LIMIT_EXCEEDED` (лимит вебхуков тарифа).
     *
     * **Тело**: `{"event": "<имя>", "timestamp": "<когда создана доставка, ISO 8601 UTC>", "data": {...}}`.
     * `timestamp` одинаков во всех повторах; время самого события внутри `data`. Поля `data` у каждого события
     * описаны в разделе Webhooks описания API (openapi.json, ключ `webhooks`).
     *
     * **Единицы сумм** (пример: 150 000 ₸)
     *
     * | Где | Поле | Единица | 150 000 ₸ |
     * |---|---|---|---|
     * | Шаблон, переменная type=currency (messages.send) | `currency.amount_1000` | тенге × 1000 | `150000000` |
     * | Сделка в REST (GET/POST/PATCH /deals) | `amount` | тиыны, тенге × 100 | `15000000` |
     * | Сделка в вебхуках deal.* | `data.deal.amount` | тиыны, тенге × 100, как в REST | `15000000` |
     * | Сделка в вебхуках deal.* | `data.deal.value` | целые тенге, оставлено для совместимости | `150000` |
     * | Счёт в вебхуке invoice.status_changed | `data.invoice.amount` | тиыны, тенге × 100 | `15000000` |
     *
     * ## Заголовки запроса
     *
     * | Заголовок | Что в нём |
     * |---|---|
     * | `X-Wazzapo-Timestamp` | Время отправки этой попытки: Unix-время в секундах, целое число, UTC, например
     * `1760000000`. В каждой попытке новое |
     * | `X-Wazzapo-Signature-V2` | `v2=` и 64 символа hex строчными буквами: HMAC-SHA256 от `{X-Wazzapo-
     * Timestamp}.{тело}`. Проверяйте этот заголовок |
     * | `X-Wazzapo-Signature` | Старая подпись без времени: `sha256=` и hex строчными, HMAC-SHA256 от тела.
     * Оставлена для совместимости, не защищает от повтора перехваченного запроса |
     * | `X-Wazzapo-Delivery-Id` | id доставки, одинаковый во всех повторах: по нему отсеивайте дубли. Его же
     * принимает POST /webhooks/{webhook_id}/deliveries/{delivery_id}/retry |
     * | `X-Wazzapo-Event` | Имя события, то же, что поле `event` тела |
     * | `X-Wazzapo-Attempt` | Номер попытки: 1 у первой, до 6 |
     * | `Content-Type` | `application/json`; `User-Agent: Wazzapo-Webhooks/1.0` |
     *
     * У тестового события (POST /webhooks/{webhook_id}/test, event=test) нет `X-Wazzapo-Delivery-Id` и `X-Wazzapo-
     * Attempt`.
     *
     * **Как проверить подпись**
     *
     * 1. Возьмите тело запроса сырыми байтами, как пришло, до разбора JSON. Не собирайте JSON заново: порядок полей,
     * отсутствие пробелов и кириллица в UTF-8 (без `\u`-экранирования) должны совпасть байт в байт.
     * 2. Строка для подписи: значение `X-Wazzapo-Timestamp`, точка, тело: `{timestamp}.{body}`.
     * 3. Ключ HMAC: секрет вебхука целиком, вместе с `whsec_`, в UTF-8 (из ответа POST /webhooks или rotate-secret).
     * 4. Ожидаемое значение: `v2=` + HMAC-SHA256(ключ, строка) в hex строчными буквами.
     * 5. Сравните с `X-Wazzapo-Signature-V2` сравнением за постоянное время (`hash_equals`,
     * `crypto.timingSafeEqual`, `hmac.compare_digest`). Не совпало: ответьте 401.
     * 6. Отклоните запрос, если `X-Wazzapo-Timestamp` отличается от вашего времени больше чем на 300 секунд.
     *
     * **Пример с готовыми значениями**: секрет `whsec_test`, `X-Wazzapo-Timestamp: 1760000000`, тело
     * `{"event":"message.incoming"}` (28 байт). Строка для подписи `1760000000.{"event":"message.incoming"}`,
     * `X-Wazzapo-Signature-V2: v2=a7e2dad2ec4a0f54d4f46ea51f2c28803b66f4effebb959c6ff8e8cc59f47fca`, старая
     * `X-Wazzapo-Signature: sha256=b8745e4a15618d503f14d3e7fb12aa3c5cf3064a4bbc50b5f515eae87ee76cd7`.
     *
     * **PHP**
     *
     * ```php
     * $body = file_get_contents('php://input');           // сырое тело
     * $ts   = $_SERVER['HTTP_X_WAZZAPO_TIMESTAMP'] ?? '';
     * $sig  = $_SERVER['HTTP_X_WAZZAPO_SIGNATURE_V2'] ?? '';
     * $expected = 'v2=' . hash_hmac('sha256', $ts . '.' . $body, $secret);
     * if (!hash_equals($expected, $sig) || abs(time() - (int)$ts) > 300) { http_response_code(401); exit; }
     * $event = json_decode($body, true);
     * http_response_code(200);
     * ```
     *
     * **Node.js (Express)**
     *
     * ```js
     * app.post('/hooks/wazzapo', express.raw({ type: 'application/json' }), (req, res) => {
     *   const ts = req.get('X-Wazzapo-Timestamp') || '';
     *   const sig = req.get('X-Wazzapo-Signature-V2') || '';
     *   const expected = 'v2=' + crypto.createHmac('sha256', SECRET)
     *     .update(ts + '.').update(req.body).digest('hex');   // req.body: Buffer
     *   const ok = sig.length === expected.length &&
     *     crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected));
     *   if (!ok || Math.abs(Date.now() / 1000 - Number(ts)) > 300) return res.sendStatus(401);
     *   res.sendStatus(200);                                 // ответ сразу, обработка потом
     *   const event = JSON.parse(req.body.toString('utf8'));
     * });
     * ```
     *
     * **Ответ и повторы.** Ответьте любым 2xx за 10 секунд; тело ответа не важно. Иначе повторы через 1 мин, 5 мин,
     * 30 мин, 2 ч и 6 ч (±20%), всего 6 попыток, с тем же телом и `X-Wazzapo-Delivery-Id`. Доставка «хотя бы один
     * раз»: дубли возможны, отсеивайте их по `X-Wazzapo-Delivery-Id`. Порядок событий не гарантирован: сверяйте поле
     * `timestamp`. После 10 доставок подряд, не дошедших за все попытки, вебхук выключается (`is_active=false`),
     * включить снова: PATCH с `is_active=true`.
     *
     * Поля тела запроса:
     * - description (string|null): Подпись для себя, чтобы отличать приёмники, например «CRM: заявки». До 100
     *   символов
     * - event_types (list<'message.incoming'|'message.outgoing'|'message.status'|'conversation.assigned'|'conversati
     *   on.status_changed'|'conversation.note_added'|'session.connected'|'session.disconnected'|'deal.stage_changed'
     *   |'deal.closed'|'deal.created'|'deal.changed'|'contact.created'|'contact.updated'|'template.status_updated'|'
     *   template.quality_changed'|'template.category_changed'|'invoice.status_changed'|'task.created'>,
     *   обязательно): На какие события подписаться, хотя бы одно; список с описаниями: GET /webhook-event-types.
     *   Сейчас: message.incoming, message.outgoing, message.status, conversation.assigned,
     *   conversation.status_changed, conversation.note_added, session.connected, session.disconnected,
     *   deal.stage_changed, deal.closed, deal.created, deal.changed, contact.created, contact.updated,
     *   template.status_updated, template.quality_changed, template.category_changed, invoice.status_changed,
     *   task.created
     * - url (string, обязательно): Адрес вашего сервера, куда Wazzapo отправляет события запросом POST: только
     *   https:// и только публичный адрес (localhost и внутренние сети нельзя), до 2048 символов. Пример:
     *   https://crm.example.kz/hooks/wazzapo
     *
     * @param array{description?: string|null, event_types: list<'message.incoming'|'message.outgoing'|'message.status'|'conversation.assigned'|'conversation.status_changed'|'conversation.note_added'|'session.connected'|'session.disconnected'|'deal.stage_changed'|'deal.closed'|'deal.created'|'deal.changed'|'contact.created'|'contact.updated'|'template.status_updated'|'template.quality_changed'|'template.category_changed'|'invoice.status_changed'|'task.created'>, url: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{consecutive_failures?: int, created_at?: string|null, description?: string|null, event_types: list<string>, id: string, is_active: bool, last_failure_at?: string|null, last_success_at?: string|null, secret: string, url: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function create(array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/webhooks', $query, $body, $options, 'json');
    }

    /**
     * Удалить вебхук
     *
     * Удаляет вебхук вместе с историей доставок: события на адрес больше не приходят.
     *
     * Чтобы остановить на время, лучше выключите: PATCH с is_active=false. Ответ 204 без тела.
     * Ошибки: 404 webhook_not_found.
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return null
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function delete(string $webhookId, array $params = []): ?array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('DELETE', '/webhooks/' . rawurlencode($webhookId), $query, null, $options, 'json');
    }

    /**
     * Вебхук
     *
     * Один вебхук: адрес, события, включён ли и сколько доставок подряд не дошли. Без секрета.
     *
     * Ошибки: 404 webhook_not_found.
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{consecutive_failures?: int, created_at?: string|null, description?: string|null, event_types: list<string>, id: string, is_active: bool, last_failure_at?: string|null, last_success_at?: string|null, url: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function get(string $webhookId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/webhooks/' . rawurlencode($webhookId), $query, null, $options, 'json');
    }

    /**
     * Список вебхуков
     *
     * Все вебхуки аккаунта: созданные через API v1, v2 и в кабинете. Секреты не показываются.
     *
     * Смотрите is_active и consecutive_failures: так видно вебхук, выключенный после 10 провалов подряд.
     * Ошибки: 401 неверный ключ.
     *
     * Параметры запроса:
     * - limit (int): Сколько записей на странице
     * - cursor (string|null): next_cursor из прошлого ответа; без него первая страница
     *
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/webhooks', $query, null, $options, 'json');
    }

    /**
     * Все страницы webhooks.list подряд: обходит next_cursor сам.
     *
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{consecutive_failures?: int, created_at?: string|null, description?: string|null, event_types: list<string>, id: string, is_active: bool, last_failure_at?: string|null, last_success_at?: string|null, url: string}>
     */
    public function listAll(array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/webhooks', $query, $options);
    }

    /**
     * История доставок
     *
     * История доставок вебхука, новые первыми: событие, статус, номер попытки, ответ вашего адреса.
     *
     * Смотрите здесь, почему события не доходят (error_message, response_status) и когда следующая
     * попытка (next_retry_at). Не дошедшую доставку можно отправить ещё раз методом retry.
     * Ошибки: 404 webhook_not_found.
     *
     * Параметры запроса:
     * - limit (int): Сколько записей на странице
     * - cursor (string|null): next_cursor из прошлого ответа; без него первая страница
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function listDeliveries(string $webhookId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/webhooks/' . rawurlencode($webhookId) . '/deliveries', $query, null, $options, 'json');
    }

    /**
     * Все страницы webhooks.list_deliveries подряд: обходит next_cursor сам.
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{attempt: int, created_at?: string|null, error_message?: string|null, event_type: string, id: string, next_retry_at?: string|null, response_status?: int|null, sent_at?: string|null, status: string}>
     */
    public function listDeliveriesAll(string $webhookId, array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/webhooks/' . rawurlencode($webhookId) . '/deliveries', $query, $options);
    }

    /**
     * Повторить доставку
     *
     * Сразу отправляет доставку ещё раз, не дожидаясь расписания повторов, и ждёт ответ.
     *
     * Нужен, когда вы починили приёмник и не хотите ждать следующей попытки. Тело и
     * X-Wazzapo-Delivery-Id те же, что раньше: дубль отсеется по нему. Счётчик attempt растёт.
     * Ошибки: 404 webhook_not_found, 404 webhook_delivery_not_found.
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param string $deliveryId id доставки: поле id из GET /webhooks/{webhook_id}/deliveries или заголовок X-Wazzapo-Delivery-Id запроса, пришедшего на ваш адрес
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{delivered: bool, delivery_id: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function retryDelivery(string $webhookId, string $deliveryId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/webhooks/' . rawurlencode($webhookId) . '/deliveries/' . rawurlencode($deliveryId) . '/retry', $query, null, $options, 'json');
    }

    /**
     * Сменить секрет подписи
     *
     * Выдаёт новый секрет подписи, старый перестаёт действовать сразу.
     *
     * Нужен, если секрет потерян или мог утечь. Новый секрет показывается один раз: сразу замените
     * его на своём сервере, иначе проверка подписи у вас перестанет проходить. Ошибки: 404 webhook_not_found.
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{id: string, secret: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function rotateSecret(string $webhookId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/webhooks/' . rawurlencode($webhookId) . '/rotate-secret', $query, null, $options, 'json');
    }

    /**
     * Отправить тестовое событие
     *
     * Сразу отправляет на адрес вебхука тестовое событие test с настоящей подписью и ждёт ответ.
     *
     * Проверьте так приёмник и проверку подписи до боевых событий. Повторов нет, в историю не пишется.
     * Результат в ответе: delivered, код и время ответа, ошибка. Ошибки: 404 webhook_not_found.
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{delivered: bool, error?: string|null, response_status?: int|null, response_time_ms?: int|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function test(string $webhookId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/webhooks/' . rawurlencode($webhookId) . '/test', $query, null, $options, 'json');
    }

    /**
     * Изменить вебхук
     *
     * Меняет адрес, события, подпись или включает и выключает вебхук. Непереданные поля не меняются.
     *
     * event_types заменяют весь список. is_active=false выключает доставку, is_active=true включает
     * снова и обнуляет счётчик провалов (так включают вебхук после автоотключения). Секрет не меняется.
     * Ошибки: 404 webhook_not_found, 422 invalid_event_types, 422 адрес не https.
     *
     * Поля тела запроса:
     * - description (string|null): Подпись для себя, чтобы отличать приёмники, например «CRM: заявки». До 100
     *   символов
     * - event_types (list<'message.incoming'|'message.outgoing'|'message.status'|'conversation.assigned'|'conversati
     *   on.status_changed'|'conversation.note_added'|'session.connected'|'session.disconnected'|'deal.stage_changed'
     *   |'deal.closed'|'deal.created'|'deal.changed'|'contact.created'|'contact.updated'|'template.status_updated'|'
     *   template.quality_changed'|'template.category_changed'|'invoice.status_changed'|'task.created'>|null): Новый
     *   список событий целиком: заменяет старый. Чтобы добавить событие, передайте старые вместе с новым. На какие
     *   события подписаться, хотя бы одно; список с описаниями: GET /webhook-event-types. Сейчас: message.incoming,
     *   message.outgoing, message.status, conversation.assigned, conversation.status_changed,
     *   conversation.note_added, session.connected, session.disconnected, deal.stage_changed, deal.closed,
     *   deal.created, deal.changed, contact.created, contact.updated, template.status_updated,
     *   template.quality_changed, template.category_changed, invoice.status_changed, task.created
     * - is_active (bool|null): false выключает доставку: события за это время не копятся и не придут потом. true
     *   включает снова и обнуляет consecutive_failures: так включают вебхук, выключенный автоматически после 10
     *   провалов подряд
     * - url (string|null): Новый адрес. Адрес вашего сервера, куда Wazzapo отправляет события запросом POST: только
     *   https:// и только публичный адрес (localhost и внутренние сети нельзя), до 2048 символов. Пример:
     *   https://crm.example.kz/hooks/wazzapo
     *
     * @param string $webhookId id вебхука: поле id из ответа POST /webhooks или из GET /webhooks, например 6712f0c4a1b2c3d4e5f60719
     * @param array{description?: string|null, event_types?: list<'message.incoming'|'message.outgoing'|'message.status'|'conversation.assigned'|'conversation.status_changed'|'conversation.note_added'|'session.connected'|'session.disconnected'|'deal.stage_changed'|'deal.closed'|'deal.created'|'deal.changed'|'contact.created'|'contact.updated'|'template.status_updated'|'template.quality_changed'|'template.category_changed'|'invoice.status_changed'|'task.created'>|null, is_active?: bool|null, url?: string|null} $body
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{consecutive_failures?: int, created_at?: string|null, description?: string|null, event_types: list<string>, id: string, is_active: bool, last_failure_at?: string|null, last_success_at?: string|null, url: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function update(string $webhookId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('PATCH', '/webhooks/' . rawurlencode($webhookId), $query, $body, $options, 'json');
    }
}
