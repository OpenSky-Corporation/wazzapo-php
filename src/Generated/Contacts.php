<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы contacts: contacts.create, contacts.delete, contacts.get, contacts.list, contacts.update. */
final class Contacts
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Создать контакт
     *
     * Добавляет клиента в контакты: номер обязателен, имя, почта, метки, свои поля и external_id по желанию.
     *
     * Номер приводится к виду 77071234567: плюс можно, 8 в начале заменяется на 7. Удалённый ранее
     * контакт с этим номером восстанавливается с новыми данными. Свои поля сначала создайте в кабинете,
     * раздел «Поля контакта». Заголовок Idempotency-Key защищает от двойного создания при повторе запроса.
     * Ошибки: 409 contact_exists (номер уже есть: id в error.details.contact_id, измените его через
     * PATCH /contacts/{contact_id}), 422 invalid_phone, 422 unknown_custom_field, 403 USAGE_LIMIT_EXCEEDED
     * (лимит контактов тарифа).
     *
     * Поля тела запроса:
     * - custom_fields (array<string, mixed>): Свои поля контакта из раздела «Поля контакта» в кабинете. Ключ: имя
     *   переменной поля (колонка «Переменная», например city или birthday; у поля без переменной его id). Значение
     *   по типу поля: текст и список строкой, число числом, флажок true/false, дата строкой ГГГГ-ММ-ДД. Пример:
     *   {"city": "Алматы", "orders_count": 3}. Поля, которых нет в кабинете: ошибка 422 unknown_custom_field
     * - email (string|null): Почта клиента, например aigul@example.kz
     * - external_id (string|null): Ваш id этого клиента в своей CRM или учётной системе, например 1c-00042. Wazzapo
     *   его не проверяет и не требует уникальности; найти контакт по нему: GET /contacts?external_id=1c-00042. До
     *   200 символов
     * - name (string|null): Имя клиента, как его видят менеджеры в диалогах и сделках, например Айгуль Серикова. До
     *   200 символов
     * - phone (string, обязательно): Номер клиента: цифры с кодом страны, плюс в начале можно: 77071234567 или
     *   +77071234567. Пробелы, скобки и дефисы не принимаются (422 VALIDATION_ERROR). 8 в начале казахстанского
     *   номера заменяется на 7 (87071234567 сохранится как 77071234567). Номер не похож на настоящий: 422
     *   invalid_phone. Номер уже есть у контакта: 409 contact_exists
     * - tags (list<string>): Метки контакта: любые строки, например vip или опт; по ним фильтрует ?tag= и сегменты
     *   рассылок. До 100 меток
     *
     * @param array{custom_fields?: array<string, mixed>, email?: string|null, external_id?: string|null, name?: string|null, phone: string, tags?: list<string>} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{attribution?: array<string, mixed>|null, created_at?: string|null, custom_fields?: array<string, mixed>, email?: string|null, external_id?: string|null, id: string, name?: string|null, phone?: string|null, source: string, tags?: list<string>, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function create(array $body, array $options = []): array
    {
        return $this->http->request('POST', '/contacts', [], $body, $options, 'json');
    }

    /**
     * Удалить контакт
     *
     * Удаляет контакт мягко, как в кабинете: он пропадает из списка и поиска, переписка остаётся.
     *
     * Восстановить: клиент напишет с этого номера или вызовите POST /contacts с тем же номером.
     * Ответ 204 без тела. Ошибки: 404 contact_not_found (уже удалён или чужой).
     *
     * @param string $contactId id контакта: поле id из GET /contacts или из ответа POST /contacts, например 6712f0c4a1b2c3d4e5f60718
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return null
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function delete(string $contactId, array $options = []): ?array
    {
        return $this->http->request('DELETE', '/contacts/' . rawurlencode($contactId), [], null, $options, 'json');
    }

    /**
     * Получить контакт
     *
     * Карточка одного контакта: номер, имя, метки, свои поля, источник (attribution).
     *
     * id берите из GET /contacts (найти по номеру: ?search=77071234567) или из ответа создания.
     * Ошибки: 404 contact_not_found, если контакта нет в этом аккаунте или он удалён.
     *
     * @param string $contactId id контакта: поле id из GET /contacts или из ответа POST /contacts, например 6712f0c4a1b2c3d4e5f60718
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{attribution?: array<string, mixed>|null, created_at?: string|null, custom_fields?: array<string, mixed>, email?: string|null, external_id?: string|null, id: string, name?: string|null, phone?: string|null, source: string, tags?: list<string>, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function get(string $contactId, array $options = []): array
    {
        return $this->http->request('GET', '/contacts/' . rawurlencode($contactId), [], null, $options, 'json');
    }

    /**
     * Список контактов
     *
     * Контакты аккаунта постранично, старые первыми. Удалённые и объединённые не показываются.
     *
     * Фильтры складываются: ?search=Айгуль&tag=vip. Найти клиента своей CRM: ?external_id=1c-00042.
     * Следующая страница: передайте next_cursor из ответа в ?cursor=, пока has_more=true.
     * Ошибки: 401 неверный ключ, 422 неверный cursor или updated_since.
     *
     * Параметры запроса:
     * - search (string|null): Поиск без учёта регистра по части имени (Айгуль) или номера от 3 цифр (7071234, 8 707
     *   123 45 67, +7 707 123-45-67)
     * - tag (string|null): Только контакты с этой меткой, например vip
     * - updated_since (string|null): Только изменённые с этого времени, ISO 8601: 2026-10-01T00:00:00Z. Удобно для
     *   обмена с вашей CRM: запоминайте время прошлого запроса
     * - external_id (string|null): Только контакты с этим external_id (вашим id из своей CRM), например 1c-00042
     * - limit (int): Сколько контактов на странице, от 1 до 200
     * - cursor (string|null): next_cursor из прошлого ответа; без него первая страница
     *
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = [], array $options = []): array
    {
        return $this->http->request('GET', '/contacts', $params, null, $options, 'json');
    }

    /**
     * Все страницы contacts.list подряд: обходит next_cursor сам.
     *
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return \Generator<int, array{attribution?: array<string, mixed>|null, created_at?: string|null, custom_fields?: array<string, mixed>, email?: string|null, external_id?: string|null, id: string, name?: string|null, phone?: string|null, source: string, tags?: list<string>, updated_at?: string|null}>
     */
    public function listAll(array $params = [], array $options = []): \Generator
    {
        return $this->http->paginate('/contacts', $params, $options);
    }

    /**
     * Изменить контакт
     *
     * Меняет только переданные поля, остальные остаются как были. Номер не меняется.
     *
     * tags заменяют весь список меток. custom_fields дополняют сохранённые: переданный ключ
     * перезаписывается, null очищает поле, непереданные не трогаются. Изменение видно менеджерам
     * сразу и запускает сценарии на изменение полей, как правка в кабинете.
     * Ошибки: 404 contact_not_found, 422 unknown_custom_field.
     *
     * Поля тела запроса:
     * - custom_fields (array<string, mixed>|null): Меняются только переданные ключи, остальные поля остаются;
     *   значение null очищает поле. Свои поля контакта из раздела «Поля контакта» в кабинете. Ключ: имя переменной
     *   поля (колонка «Переменная», например city или birthday; у поля без переменной его id). Значение по типу
     *   поля: текст и список строкой, число числом, флажок true/false, дата строкой ГГГГ-ММ-ДД. Пример: {"city":
     *   "Алматы", "orders_count": 3}. Поля, которых нет в кабинете: ошибка 422 unknown_custom_field
     * - email (string|null): Почта клиента, например aigul@example.kz
     * - external_id (string|null): Ваш id этого клиента в своей CRM или учётной системе, например 1c-00042. Wazzapo
     *   его не проверяет и не требует уникальности; найти контакт по нему: GET /contacts?external_id=1c-00042
     * - name (string|null): Имя клиента, как его видят менеджеры в диалогах и сделках, например Айгуль Серикова. До
     *   200 символов
     * - tags (list<string>|null): Новый список меток целиком: заменяет старый. [] или null убирают все метки. Чтобы
     *   добавить метку, передайте старые метки вместе с новой
     *
     * @param string $contactId id контакта: поле id из GET /contacts или из ответа POST /contacts, например 6712f0c4a1b2c3d4e5f60718
     * @param array{custom_fields?: array<string, mixed>|null, email?: string|null, external_id?: string|null, name?: string|null, tags?: list<string>|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{attribution?: array<string, mixed>|null, created_at?: string|null, custom_fields?: array<string, mixed>, email?: string|null, external_id?: string|null, id: string, name?: string|null, phone?: string|null, source: string, tags?: list<string>, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function update(string $contactId, array $body, array $options = []): array
    {
        return $this->http->request('PATCH', '/contacts/' . rawurlencode($contactId), [], $body, $options, 'json');
    }
}
