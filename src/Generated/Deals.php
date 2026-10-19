<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы deals: deals.create, deals.get, deals.list, deals.move, deals.update. */
final class Deals
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Создать сделку
     *
     * Создаёт сделку, например из заявки с сайта, так же, как окно «Новая сделка» в кабинете.
     *
     * До вызова: pipeline_id и stage_id из GET /pipelines, ответственный из GET /team. Клиент:
     * contact_id из GET /contacts или contact {phone, name}: найдём контакт по номеру или создадим.
     * Сумма amount целым в тиынах, не в тенге: 150 000 ₸ = 15000000 (в шаблоне WhatsApp
     * сумма другая: amount_1000 = 150000000). custom_fields по id полей воронки.
     * Без дублей: передайте свой external_id. Повтор формы с тем же external_id, который уже
     * есть у сделки аккаунта, сделку не создаёт: 409 deal_exists, id существующей в
     * details.deal_id. Два одновременных запроса закрывает ещё и заголовок Idempotency-Key:
     * шлите тот же ключ при повторе после сбоя сети.
     * responsible_user_id ставит ответственного только сделки: ответственный переписки не
     * меняется, для него POST /conversations/{conversation_id}/assign.
     * Срабатывают действия этапа и уходит вебхук deal.created. Право deals:write.
     * Частые ошибки: 404 pipeline_not_found, 422 stage_not_found, unknown_custom_field,
     * amount_field_missing (в воронке нет поля суммы), responsible_user_not_found (нет такого
     * сотрудника или он отключён: is_active false в GET /team), VALIDATION_ERROR (нет ни
     * contact_id, ни contact), 404 contact_not_found, 409 deal_exists. Код open_deal_exists
     * (у переписки уже есть открытая сделка) этот метод не возвращает: сделка из API не
     * проверяет переписку и создаётся, даже если у клиента есть открытая в этой воронке.
     *
     * Поля тела запроса:
     * - amount (int|null): Сумма сделки. Целое число в тиынах: 1 ₸ = 100 тиынов, 150 000 ₸ = 15000000. Целым, чтобы
     *   сумма не искажалась при округлении дробей. Пишется в поле суммы воронки (is_amount). Внимание: в тиынах (×
     *   100), а не в тенге: 150 000 ₸ = 15000000. В шаблоне WhatsApp (messages.send, currency.amount_1000) множитель
     *   другой, × 1000: 150 000 ₸ = 150000000
     * - contact (array<string, mixed>|null): Клиент по телефону, если id контакта не знаете: найдём по номеру или
     *   создадим
     * - contact_id (string|null): id существующего контакта из GET /contacts. Передайте его или contact с телефоном
     * - currency (string|null): Валюта суммы, должна совпасть с currency поля суммы воронки (обычно KZT), иначе 422
     *   currency_mismatch
     * - custom_fields (array<string, mixed>): Свои поля сделки: ключ: id поля из GET /pipelines (custom_fields[].id
     *   воронки), значение по типу поля: text и multiline строкой, number числом, money целым в тиынах, select одной
     *   строкой из options, date строкой ГГГГ-ММ-ДД, checkbox true или false. Например {"9c8b7a6d5e4f": "Сайт",
     *   "3e4f5a6b7c8d": "Астана", "7d8e9f0a1b2c": "2026-10-21"}. Поля не из этой воронки: 422 unknown_custom_field
     * - external_id (string|null): id этой сделки в вашей системе (CRM, сайт, 1С), до 200 знаков, например site-
     *   order-4521. Повтор POST /deals с тем же external_id (например, клиент дважды отправил форму) сделку не
     *   создаёт: 409 deal_exists, id существующей сделки в details.deal_id. Одновременные запросы закрывает ещё и
     *   заголовок Idempotency-Key. Найти сделку: GET /deals?external_id=...
     * - pipeline_id (string, обязательно): id воронки: поле id из GET /pipelines, например 6712a0c4e1b2c3d4e5f60718
     * - responsible_user_id (string|null): Ответственный: id сотрудника строкой: поле id из GET /team, например
     *   "12". Не передали: без ответственного (у ключа сотрудника с ограниченным доступом: сам сотрудник)
     * - stage_id (string|null): id этапа: stages[].id нужной воронки из GET /pipelines, например
     *   a1b2c3d4e5f6a7b8c9d0e1f2. Не передали: первый рабочий этап воронки
     * - title (string|null): Название, до 300 знаков. Не передали: номер сделки, например #0128
     *
     * @param array{amount?: int|null, contact?: array<string, mixed>|null, contact_id?: string|null, currency?: string|null, custom_fields?: array<string, mixed>, external_id?: string|null, pipeline_id: string, responsible_user_id?: string|null, stage_id?: string|null, title?: string|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{amount?: int, closed_at?: string|null, contact: array<string, mixed>, conversation_id?: string|null, created_at?: string|null, currency?: string, custom_fields?: array<string, mixed>, external_id?: string|null, id: string, lost_at?: string|null, lost_reason?: string|null, number: int, pipeline_id: string, responsible_user_id?: string|null, source?: string|null, stage_id: string, stage_name?: string, status: 'open'|'won'|'lost', title: string, updated_at?: string|null, won_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function create(array $body, array $options = []): array
    {
        return $this->http->request('POST', '/deals', [], $body, $options, 'json');
    }

    /**
     * Сделка
     *
     * Одна сделка: этап, сумма в тиынах, клиент, ответственный, свои поля.
     *
     * Сделка чужого аккаунта или скрытая от сотрудника, которому выдан ключ: 404 deal_not_found.
     * Право deals:read.
     *
     * @param string $dealId id сделки: поле id из GET /deals или из ответа POST /deals, например 6713b1d5f2c3d4e5f6071829
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{amount?: int, closed_at?: string|null, contact: array<string, mixed>, conversation_id?: string|null, created_at?: string|null, currency?: string, custom_fields?: array<string, mixed>, external_id?: string|null, id: string, lost_at?: string|null, lost_reason?: string|null, number: int, pipeline_id: string, responsible_user_id?: string|null, source?: string|null, stage_id: string, stage_name?: string, status: 'open'|'won'|'lost', title: string, updated_at?: string|null, won_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function get(string $dealId, array $options = []): array
    {
        return $this->http->request('GET', '/deals/' . rawurlencode($dealId), [], null, $options, 'json');
    }

    /**
     * Список сделок
     *
     * Сделки аккаунта по времени изменения, старые первыми, страницами по cursor.
     *
     * Синхронизация с вашей системой: запомните updated_at последней сделки и в следующий раз
     * передайте его в updated_since. Дубль перед созданием ищут по external_id.
     * Сумма amount и денежные custom_fields в тиынах (150 000 ₸ = 15000000).
     * Ключ сотрудника видит те же сделки, что сотрудник в кабинете. Право deals:read.
     *
     * Параметры запроса:
     * - pipeline_id (string|null): Только сделки воронки: id из GET /pipelines
     * - stage_id (string|null): Только сделки на этапе: stages[].id из GET /pipelines
     * - contact_id (string|null): Только сделки клиента: id контакта из GET /contacts
     * - external_id (string|null): Найти сделку по вашему id (external_id при создании), например site-order-4521:
     *   так проверяют дубль перед POST /deals
     * - updated_since (string|null): Только изменённые начиная с этого времени, ISO 8601, например
     *   2026-10-18T00:00:00Z
     * - limit (int): Сколько сделок на странице, от 1 до 200, по умолчанию 50
     * - cursor (string|null): next_cursor из прошлого ответа; для первой страницы не передавайте
     *
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = [], array $options = []): array
    {
        return $this->http->request('GET', '/deals', $params, null, $options, 'json');
    }

    /**
     * Все страницы deals.list подряд: обходит next_cursor сам.
     *
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return \Generator<int, array{amount?: int, closed_at?: string|null, contact: array<string, mixed>, conversation_id?: string|null, created_at?: string|null, currency?: string, custom_fields?: array<string, mixed>, external_id?: string|null, id: string, lost_at?: string|null, lost_reason?: string|null, number: int, pipeline_id: string, responsible_user_id?: string|null, source?: string|null, stage_id: string, stage_name?: string, status: 'open'|'won'|'lost', title: string, updated_at?: string|null, won_at?: string|null}>
     */
    public function listAll(array $params = [], array $options = []): \Generator
    {
        return $this->http->paginate('/deals', $params, $options);
    }

    /**
     * Перенести сделку на этап
     *
     * Переносит сделку на другой этап её воронки, как перетаскивание карточки в кабинете.
     *
     * stage_id берите из stages[] воронки в GET /pipelines. Перенос на этап с is_won закрывает
     * сделку успехом, на этап с is_lost отказом (причину передайте в lost_reason).
     * После переноса срабатывает то же, что в кабинете: сценарии «Событие в воронке», действия
     * этапа (поставить или закрыть задачи), вебхуки deal.stage_changed и при закрытии deal.closed, передача
     * продажи или заявки в Meta, если клиент пришёл с рекламы «Написать в WhatsApp».
     * Поля, обязательные с этого этапа (custom_fields[].required_from_stage_id в GET /pipelines),
     * заполните заранее через PATCH /deals/{deal_id}, иначе
     * 422 required_fields со списком полей в details. Другая воронка: 422 pipeline_change_not_supported.
     * Право deals:write.
     *
     * Поля тела запроса:
     * - lost_reason (string|null): Причина отказа при переносе на этап с is_lost, до 500 знаков, например «Дорого»
     * - pipeline_id (string|null): Воронка этапа; можно не передавать. Другая воронка пока не поддерживается: 422
     *   pipeline_change_not_supported
     * - stage_id (string, обязательно): id этапа: stages[].id нужной воронки из GET /pipelines, например
     *   a1b2c3d4e5f6a7b8c9d0e1f2 (этап той же воронки, где сделка)
     *
     * @param string $dealId id сделки: поле id из GET /deals или из ответа POST /deals, например 6713b1d5f2c3d4e5f6071829
     * @param array{lost_reason?: string|null, pipeline_id?: string|null, stage_id: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{amount?: int, closed_at?: string|null, contact: array<string, mixed>, conversation_id?: string|null, created_at?: string|null, currency?: string, custom_fields?: array<string, mixed>, external_id?: string|null, id: string, lost_at?: string|null, lost_reason?: string|null, number: int, pipeline_id: string, responsible_user_id?: string|null, source?: string|null, stage_id: string, stage_name?: string, status: 'open'|'won'|'lost', title: string, updated_at?: string|null, won_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function move(string $dealId, array $body, array $options = []): array
    {
        return $this->http->request('POST', '/deals/' . rawurlencode($dealId) . '/move', [], $body, $options, 'json');
    }

    /**
     * Изменить сделку
     *
     * Меняет название, сумму, ответственного, свои поля и external_id сделки.
     *
     * Меняются только переданные поля, остальные не трогаются. Сумма amount целым в тиынах.
     * В custom_fields передавайте только меняемые поля, null очищает поле. Снять ответственного
     * нельзя: responsible_user_id=null значит «не менять». Ответственный меняется только у
     * сделки, не у переписки (её: POST /conversations/{conversation_id}/assign); если в
     * кабинете включено правило, новому ответственному ставится задача. Этап этим методом не меняется:
     * для этого POST /deals/{deal_id}/move. В ленте сделки появится «было → стало», уходит
     * вебхук deal.changed. Право deals:write.
     * Частые ошибки: 404 deal_not_found, 422 unknown_custom_field, responsible_user_not_found
     * (нет такого сотрудника или он отключён), currency_mismatch.
     *
     * Поля тела запроса:
     * - amount (int|null): Новая сумма. Целое число в тиынах: 1 ₸ = 100 тиынов, 150 000 ₸ = 15000000. Целым, чтобы
     *   сумма не искажалась при округлении дробей. Внимание: в тиынах (× 100), а не в тенге: 150 000 ₸ = 15000000. В
     *   шаблоне WhatsApp (messages.send, currency.amount_1000) множитель другой, × 1000: 150 000 ₸ = 150000000
     * - currency (string|null): Валюта суммы, как у поля суммы воронки, обычно KZT
     * - custom_fields (array<string, mixed>|null): Свои поля сделки: ключ: id поля из GET /pipelines
     *   (custom_fields[].id воронки), значение по типу поля: text и multiline строкой, number числом, money целым в
     *   тиынах, select одной строкой из options, date строкой ГГГГ-ММ-ДД, checkbox true или false. Например
     *   {"9c8b7a6d5e4f": "Сайт", "3e4f5a6b7c8d": "Астана", "7d8e9f0a1b2c": "2026-10-21"}. Поля не из этой воронки:
     *   422 unknown_custom_field. Меняются только переданные поля, null очищает поле
     * - external_id (string|null): id этой сделки в вашей системе (CRM, сайт, 1С), до 200 знаков, например site-
     *   order-4521. При правке на повтор не проверяется. null очищает
     * - responsible_user_id (string|null): Новый ответственный: id сотрудника строкой: поле id из GET /team,
     *   например "12". null не снимает ответственного
     * - title (string|null): Новое название, 1–300 знаков
     *
     * @param string $dealId id сделки: поле id из GET /deals или из ответа POST /deals, например 6713b1d5f2c3d4e5f6071829
     * @param array{amount?: int|null, currency?: string|null, custom_fields?: array<string, mixed>|null, external_id?: string|null, responsible_user_id?: string|null, title?: string|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{amount?: int, closed_at?: string|null, contact: array<string, mixed>, conversation_id?: string|null, created_at?: string|null, currency?: string, custom_fields?: array<string, mixed>, external_id?: string|null, id: string, lost_at?: string|null, lost_reason?: string|null, number: int, pipeline_id: string, responsible_user_id?: string|null, source?: string|null, stage_id: string, stage_name?: string, status: 'open'|'won'|'lost', title: string, updated_at?: string|null, won_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function update(string $dealId, array $body, array $options = []): array
    {
        return $this->http->request('PATCH', '/deals/' . rawurlencode($dealId), [], $body, $options, 'json');
    }
}
