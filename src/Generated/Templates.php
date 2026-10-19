<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы templates: templates.create, templates.delete, templates.get, templates.get_status, templates.list, templates.update. */
final class Templates
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Отправить шаблон на проверку
     *
     * Создаёт шаблон и отправляет его на проверку Meta. Первое сообщение клиенту и сообщение после 24 часов
     * тишины можно отправить только одобренным шаблоном.
     *
     * До вызова: возьмите phone_number_id из GET /phone-numbers. Ответ приходит со status=PENDING; итог проверки
     * узнайте через GET /templates/{template_id} или событие вебхука template.status_updated.
     * Картинку, видео или PDF в шапке передайте ссылкой в example.media_url: Wazzapo сам загрузит образец в Meta.
     * Ошибки: 422 VALIDATION_ERROR (нет обязательного поля), 422 media_unavailable (образец файла не скачался),
     * 422 invalid_parameters (Meta не приняла шаблон, например имя уже занято), 404 номер не найден.
     * Повтор с тем же Idempotency-Key не создаёт второй шаблон.
     *
     * Поля тела запроса:
     * - category ('MARKETING'|'UTILITY'|'AUTHENTICATION', обязательно): UTILITY: служебное по заказу или записи
     *   клиента (подтверждение, напоминание, статус), дешевле рекламного. MARKETING: реклама, акции, скидки,
     *   приглашения. AUTHENTICATION: код подтверждения входа. Meta сама переносит в MARKETING служебный шаблон с
     *   рекламой, об этом придёт событие template.category_changed
     * - components (list<array<string, mixed>>, обязательно): Части шаблона: BODY обязателен, HEADER, FOOTER и
     *   BUTTONS по желанию, каждая не больше одной. Вид части выбирается полем type, у шапки ещё полем format
     * - language (string, обязательно): Код языка Meta: ru, kk, en, en_US, uz и др. Полный список:
     *   developers.facebook.com, раздел WhatsApp, Templates, Supported languages
     * - name (string, обязательно): Имя шаблона: латиница в нижнем регистре, цифры и _, до 512 символов, например
     *   zapis_napominanie. Уникально для номера и языка. Имя удалённого одобренного шаблона занято 30 дней
     * - phone_number_id (string, обязательно): id номера WhatsApp: поле phone_number_id из GET
     *   /api/v2/external/phone-numbers, например 123456789012345
     *
     * @param array{category: 'MARKETING'|'UTILITY'|'AUTHENTICATION', components: list<array<string, mixed>>, language: string, name: string, phone_number_id: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{buttons?: list<array<string, mixed>>|null, category: 'MARKETING'|'UTILITY'|'AUTHENTICATION', components?: list<array<string, mixed>>|null, created_at?: string|null, header_format?: 'TEXT'|'IMAGE'|'VIDEO'|'DOCUMENT'|'LOCATION'|null, language: string, name: string, quality_score?: 'HIGH'|'MEDIUM'|'LOW'|'UNKNOWN'|null, rejected_reason?: string|null, status: 'PENDING'|'APPROVED'|'REJECTED'|'DISABLED'|'PAUSED', template_id: string, updated_at?: string|null, variables_count?: int|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function create(array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/templates', $query, $body, $options, 'json');
    }

    /**
     * Удалить шаблон
     *
     * Удаляет шаблон в Meta (только этот язык) и в Wazzapo. Отменить нельзя.
     *
     * Имя удалённого одобренного шаблона Meta не даёт занять снова 30 дней; отключённый (DISABLED) шаблон
     * Meta не удаляет. Ошибки: 404 шаблон не найден. Тестовый ключ ничего не удаляет.
     *
     * @param string $templateId id шаблона в Meta: template_id из POST или GET /api/v2/external/templates, например 1252715608684590
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return null
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function delete(string $templateId, array $params = []): ?array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('DELETE', '/templates/' . rawurlencode($templateId), $query, null, $options, 'json');
    }

    /**
     * Шаблон целиком
     *
     * Шаблон со всеми частями (components), числом переменных и кнопками; статус свежий из Meta.
     *
     * Используйте перед отправкой, чтобы знать, сколько значений и какой файл шапки передать.
     * Шаблоны, созданные в WhatsApp Manager, появляются здесь после синхронизации в кабинете Wazzapo,
     * до неё ищите их в GET /templates. Ошибки: 404 NOT_FOUND, шаблона с таким id нет в этом
     * аккаунте. Это 404, потому что не найден сам адрес-объект; при отправке неизвестного шаблона
     * POST /messages/send отвечает 422 template_not_found (не подошло значение в теле запроса).
     *
     * @param string $templateId id шаблона в Meta: template_id из POST или GET /api/v2/external/templates, например 1252715608684590
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{buttons?: list<array<string, mixed>>|null, category: 'MARKETING'|'UTILITY'|'AUTHENTICATION', components?: list<array<string, mixed>>|null, created_at?: string|null, header_format?: 'TEXT'|'IMAGE'|'VIDEO'|'DOCUMENT'|'LOCATION'|null, language: string, name: string, quality_score?: 'HIGH'|'MEDIUM'|'LOW'|'UNKNOWN'|null, rejected_reason?: string|null, status: 'PENDING'|'APPROVED'|'REJECTED'|'DISABLED'|'PAUSED', template_id: string, updated_at?: string|null, variables_count?: int|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function get(string $templateId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/templates/' . rawurlencode($templateId), $query, null, $options, 'json');
    }

    /**
     * Статус шаблона
     *
     * Свежий статус проверки шаблона в Meta: PENDING, APPROVED, REJECTED, PAUSED или DISABLED.
     *
     * Короткий вариант для опроса статуса: смотрите в ответе status и rejected_reason. Полное
     * содержимое шаблона (части, переменные, кнопки) берите из GET /templates/{template_id}.
     * Опрашивайте после создания или правки, пока статус PENDING (раз в минуту достаточно), или подпишитесь
     * на событие template.status_updated. При REJECTED причина в rejected_reason.
     * Ошибки: 404 NOT_FOUND, шаблона с таким id нет в этом аккаунте (при отправке неизвестного
     * шаблона POST /messages/send отвечает 422 template_not_found).
     *
     * @param string $templateId id шаблона в Meta: template_id из POST или GET /api/v2/external/templates, например 1252715608684590
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{buttons?: list<array<string, mixed>>|null, category: 'MARKETING'|'UTILITY'|'AUTHENTICATION', components?: list<array<string, mixed>>|null, created_at?: string|null, header_format?: 'TEXT'|'IMAGE'|'VIDEO'|'DOCUMENT'|'LOCATION'|null, language: string, name: string, quality_score?: 'HIGH'|'MEDIUM'|'LOW'|'UNKNOWN'|null, rejected_reason?: string|null, status: 'PENDING'|'APPROVED'|'REJECTED'|'DISABLED'|'PAUSED', template_id: string, updated_at?: string|null, variables_count?: int|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function getStatus(string $templateId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/templates/' . rawurlencode($templateId) . '/status', $query, null, $options, 'json');
    }

    /**
     * Список шаблонов
     *
     * Все шаблоны номера прямо из Meta, со статусом и содержимым (components).
     *
     * Нужен, чтобы выбрать шаблон для messages.send: передайте его name и language, а значений переменных
     * столько, сколько variables_count. Фильтры status, category, name, language можно сочетать.
     * Страницы курсором: next_cursor передайте в cursor. У каналов без шаблонов Meta (почта, OLX, Telegram)
     * список пустой. Ошибки: 404 номер не найден, 403 account_restricted.
     *
     * Параметры запроса:
     * - phone_number_id (string, обязательно): id номера из GET /api/v2/external/phone-numbers, например
     *   123456789012345
     * - limit (int): Сколько шаблонов на странице, от 1 до 200
     * - cursor (string|null): next_cursor из прошлого ответа
     * - status (string|null): Только с этим статусом: PENDING, APPROVED, REJECTED, PAUSED, DISABLED
     * - category (string|null): Только этой категории: MARKETING, UTILITY, AUTHENTICATION
     * - name (string|null): Часть имени без учёта регистра, например zapis
     * - language (string|null): Только этого языка, например ru или kk
     *
     * @param string $phoneNumberId id номера из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{limit?: int, cursor?: string|null, status?: string|null, category?: string|null, name?: string|null, language?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, has_more?: bool, meta?: array<string, mixed>, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(string $phoneNumberId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/templates', ['phone_number_id' => $phoneNumberId] + $query, null, $options, 'json');
    }

    /**
     * Все страницы templates.list подряд: обходит next_cursor сам.
     *
     * @param string $phoneNumberId id номера из GET /api/v2/external/phone-numbers, например 123456789012345
     * @param array{limit?: int, cursor?: string|null, status?: string|null, category?: string|null, name?: string|null, language?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{buttons?: list<array<string, mixed>>|null, category: 'MARKETING'|'UTILITY'|'AUTHENTICATION', components?: list<array<string, mixed>>|null, created_at?: string|null, header_format?: 'TEXT'|'IMAGE'|'VIDEO'|'DOCUMENT'|'LOCATION'|null, language: string, name: string, quality_score?: 'HIGH'|'MEDIUM'|'LOW'|'UNKNOWN'|null, rejected_reason?: string|null, status: 'PENDING'|'APPROVED'|'REJECTED'|'DISABLED'|'PAUSED', template_id: string, updated_at?: string|null, variables_count?: int|null}>
     */
    public function listAll(string $phoneNumberId, array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/templates', ['phone_number_id' => $phoneNumberId] + $query, $options);
    }

    /**
     * Изменить шаблон
     *
     * Меняет части шаблона или категорию в Meta, после чего Meta проверяет шаблон заново (status=PENDING).
     *
     * Правила Meta: править можно шаблон в статусе APPROVED, REJECTED или PAUSED; имя и язык не меняются;
     * components заменяются целиком; категорию одобренного шаблона сменить нельзя. Одобренный шаблон можно
     * править 1 раз в сутки и не больше 10 раз за 30 дней, отклонённый без ограничений.
     * Ошибки: 404 шаблон не найден, 409 CONFLICT (статус не даёт править), 422 VALIDATION_ERROR,
     * 422 invalid_parameters (Meta отказала, например исчерпан лимит правок). Тестовый ключ ничего не меняет.
     *
     * Поля тела запроса:
     * - category ('MARKETING'|'UTILITY'|'AUTHENTICATION'|null): Новая категория: только у отклонённого или
     *   приостановленного шаблона. UTILITY: служебное по заказу или записи клиента (подтверждение, напоминание,
     *   статус), дешевле рекламного. MARKETING: реклама, акции, скидки, приглашения. AUTHENTICATION: код
     *   подтверждения входа. Meta сама переносит в MARKETING служебный шаблон с рекламой, об этом придёт событие
     *   template.category_changed
     * - components (list<array<string, mixed>>|null): Все части шаблона заново: Meta заменяет их целиком, по одной
     *   части не правится. Части шаблона: BODY обязателен, HEADER, FOOTER и BUTTONS по желанию, каждая не больше
     *   одной. Вид части выбирается полем type, у шапки ещё полем format
     *
     * @param string $templateId id шаблона в Meta: template_id из POST или GET /api/v2/external/templates, например 1252715608684590
     * @param array{category?: 'MARKETING'|'UTILITY'|'AUTHENTICATION'|null, components?: list<array<string, mixed>>|null} $body
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{buttons?: list<array<string, mixed>>|null, category: 'MARKETING'|'UTILITY'|'AUTHENTICATION', components?: list<array<string, mixed>>|null, created_at?: string|null, header_format?: 'TEXT'|'IMAGE'|'VIDEO'|'DOCUMENT'|'LOCATION'|null, language: string, name: string, quality_score?: 'HIGH'|'MEDIUM'|'LOW'|'UNKNOWN'|null, rejected_reason?: string|null, status: 'PENDING'|'APPROVED'|'REJECTED'|'DISABLED'|'PAUSED', template_id: string, updated_at?: string|null, variables_count?: int|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function update(string $templateId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('PATCH', '/templates/' . rawurlencode($templateId), $query, $body, $options, 'json');
    }
}
