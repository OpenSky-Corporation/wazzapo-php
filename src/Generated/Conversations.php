<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы conversations: conversations.add_note, conversations.assign, conversations.get, conversations.list, conversations.list_messages, conversations.set_labels, conversations.set_status. */
final class Conversations
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Добавить заметку
     *
     * Добавляет заметку сотрудника в диалог: её видят только сотрудники в кабинете, клиенту она не уходит.
     * Упомянутым в `mention_user_ids` (id из GET /team) приходит уведомление в кабинете. Автор заметки сотрудник,
     * чей ключ. Уходит вебхук `conversation.note_added` (только по явной подписке).
     *
     * Передайте заголовок `Idempotency-Key`: повтор запроса с тем же ключом не создаст вторую заметку. В переписке
     * заметка видна с `include_notes=true`.
     *
     * Ошибки: 403 `FORBIDDEN`, 404 `conversation_not_found`, 422 `VALIDATION_ERROR` (пустой текст или id не из GET
     * /team). Право ключа: `conversations:write`.
     *
     * Поля тела запроса:
     * - mention_user_ids (list<string>): Кого упомянуть: id сотрудников из GET /api/v2/external/team, например
     *   ["7"], до 50. Им придёт уведомление в кабинете; имя в тексте (@Ерлан) уведомление не отправляет
     * - text (string, обязательно): Текст заметки до 4096 символов, например «Перезвонить после 18:00». Клиент её не
     *   видит
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{mention_user_ids?: list<string>, text: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{channel: string, contact: array<string, mixed>, conversation_id?: string|null, direction: 'in'|'out', id: string, interactive?: array<string, mixed>|null, is_deleted?: bool, is_note?: bool, media_url?: string|null, sender: array<string, mixed>, session_id: string, status?: string|null, template?: array<string, mixed>|null, text?: string, timestamp?: string|null, type: string}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function addNote(string $conversationId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/conversations/' . rawurlencode($conversationId) . '/notes', $query, $body, $options, 'json');
    }

    /**
     * Назначить ответственного
     *
     * Назначает ответственного менеджера (`user_id`) или снимает его (`null`). id сотрудника возьмите из GET /team:
     * поле `id`, сотрудник должен быть активным (`is_active: true`) и не VIEWER.
     *
     * Права как в чате: назначить на другого может владелец, администратор команды или текущий ответственный; взять
     * себе чужой диалог менеджер может, если владелец это разрешил. Новому ответственному приходит уведомление в
     * кабинете, в диалог пишется служебная заметка, уходит вебхук `conversation.assigned`.
     *
     * Ошибки: 403 `FORBIDDEN` (у сотрудника ключа нет права назначать или выбран наблюдатель VIEWER), 404
     * `conversation_not_found`, 409 `CONFLICT` (сотрудник отключён, `is_active: false`, или его нет в команде
     * аккаунта; или диалог одновременно назначили из кабинета: перечитайте и повторите), 422 `VALIDATION_ERROR` (в
     * user_id не только цифры, например «abc»: передайте id строкой из GET /team, например «7»). В сделках и задачах
     * тот же отключённый сотрудник даёт 422 `responsible_user_not_found`: там ответственный проверяется как поле
     * тела запроса. Право ключа: `conversations:write`.
     *
     * Поля тела запроса:
     * - user_id (string|null, обязательно): id сотрудника строкой из цифр: поле id из GET /api/v2/external/team,
     *   например "7" (можно и числом 7). Поле обязательно: чтобы снять ответственного, передайте {"user_id": null}.
     *   Сотрудник отключён или не из команды: 409 CONFLICT, наблюдатель: 403 FORBIDDEN
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{user_id: string|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{assignee?: array<string, mixed>|null, channel: string, contact: array<string, mixed>, created_at?: string|null, display_id: int, id: string, is_group?: bool, last_message: array<string, mixed>, phone_number_id?: string|null, session_id: string, snoozed_until?: string|null, status: 'open'|'pending'|'snoozed'|'resolved', tags?: list<string>, unread_count?: int, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function assign(string $conversationId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/conversations/' . rawurlencode($conversationId) . '/assign', $query, $body, $options, 'json');
    }

    /**
     * Диалог
     *
     * Один диалог по id: контакт, канал, статус, ответственный, метки, непрочитанные и последнее сообщение. id берут
     * из GET /conversations или из поля `conversation_id` вебхука `message.incoming`.
     *
     * Ошибка 404 `conversation_not_found`: диалога нет в этом аккаунте или он скрыт от сотрудника, чей ключ. Право
     * ключа: `conversations:read`.
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{assignee?: array<string, mixed>|null, channel: string, contact: array<string, mixed>, created_at?: string|null, display_id: int, id: string, is_group?: bool, last_message: array<string, mixed>, phone_number_id?: string|null, session_id: string, snoozed_until?: string|null, status: 'open'|'pending'|'snoozed'|'resolved', tags?: list<string>, unread_count?: int, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function get(string $conversationId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/conversations/' . rawurlencode($conversationId), $query, null, $options, 'json');
    }

    /**
     * Список диалогов
     *
     * Диалоги аккаунта из всех каналов (WhatsApp, Telegram, Instagram, чат на сайте, OLX, Satu, почта),
     * отсортированные по `updated_at` от давно изменённых к недавним.
     *
     * **Синхронизация.** Первый раз выгрузите всё, листая по `next_cursor`, пока `has_more` не станет false.
     * Запомните `updated_at` последнего диалога и в следующий раз передайте его в `updated_since`: придут только
     * диалоги, изменённые после (новое сообщение, статус, ответственный, метки). Диалог, изменённый во время
     * листания, придёт ещё раз в конце, изменения не теряются. Чтобы узнавать сразу, подпишитесь на вебхуки
     * `message.incoming` и `conversation.*`.
     *
     * Фильтры складываются: `status=open&assignee_user_id=none` даёт открытые диалоги без ответственного. Ключ
     * сотрудника видит и меняет то же, что сотрудник в чате кабинета: диалоги по правилам видимости команды и только
     * в доступных ему каналах.
     *
     * Ошибки: 404 `phone_number_not_found` (чужой или неверный phone_number_id), 422 `VALIDATION_ERROR` (неверный
     * assignee_user_id или cursor). Право ключа: `conversations:read`.
     *
     * Параметры запроса:
     * - phone_number_id (string|null): Только диалоги этого номера WhatsApp: phone_number_id из GET
     *   /api/v2/external/phone-numbers, например 103799815708451. Если передан, channel не учитывается
     * - channel ('whatsapp'|'telegram'|'instagram'|'olx'|'satu'|'email'|'webchat'|null): Только диалоги канала:
     *   whatsapp, telegram, instagram, webchat (чат на сайте), olx, satu, email
     * - status ('open'|'pending'|'snoozed'|'resolved'|null): Только диалоги в статусе: open (открыт, нужно
     *   ответить), pending (ожидает), snoozed (отложен), resolved (решён)
     * - assignee_user_id (string|null): Только диалоги ответственного: id из GET /api/v2/external/team, например 7;
     *   none: диалоги без ответственного
     * - unread (bool|null): true: есть непрочитанные сообщения клиента, false: всё прочитано
     * - updated_since (string|null): Только диалоги, изменённые после этого момента, ISO 8601, например
     *   2026-10-01T00:00:00Z. Для синхронизации: updated_at последнего диалога прошлой выгрузки
     * - limit (int): Диалогов на странице, от 1 до 200
     * - cursor (string|null): next_cursor из прошлого ответа; без него первая страница
     *
     * @param array{phone_number_id?: string|null, channel?: 'whatsapp'|'telegram'|'instagram'|'olx'|'satu'|'email'|'webchat'|null, status?: 'open'|'pending'|'snoozed'|'resolved'|null, assignee_user_id?: string|null, unread?: bool|null, updated_since?: string|null, limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/conversations', $query, null, $options, 'json');
    }

    /**
     * Все страницы conversations.list подряд: обходит next_cursor сам.
     *
     * @param array{phone_number_id?: string|null, channel?: 'whatsapp'|'telegram'|'instagram'|'olx'|'satu'|'email'|'webchat'|null, status?: 'open'|'pending'|'snoozed'|'resolved'|null, assignee_user_id?: string|null, unread?: bool|null, updated_since?: string|null, limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{assignee?: array<string, mixed>|null, channel: string, contact: array<string, mixed>, created_at?: string|null, display_id: int, id: string, is_group?: bool, last_message: array<string, mixed>, phone_number_id?: string|null, session_id: string, snoozed_until?: string|null, status: 'open'|'pending'|'snoozed'|'resolved', tags?: list<string>, unread_count?: int, updated_at?: string|null}>
     */
    public function listAll(array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/conversations', $query, $options);
    }

    /**
     * Переписка диалога
     *
     * Переписка диалога от старых сообщений к новым: клиент, менеджеры, сценарии, боты, рассылки и API (кто написал,
     * в `sender.type`). Листайте по `next_cursor`, на последней странице `next_cursor: null`.
     *
     * **Только новые сообщения.** Передайте в `since` поле `timestamp` последнего полученного сообщения: придут
     * сообщения после него. Сразу узнавать о новых удобнее вебхуком `message.incoming`.
     *
     * Заметки сотрудников (клиент их не видит) приходят только с `include_notes=true`, у них `is_note: true`. Ошибка
     * 404 `conversation_not_found`. Право ключа: `conversations:read`.
     *
     * Параметры запроса:
     * - include_notes (bool): true: добавить заметки сотрудников (is_note: true), клиент их не видит
     * - since (string|null): Только сообщения после этого времени, ISO 8601, например 2026-10-18T09:15:00Z:
     *   timestamp последнего полученного сообщения
     * - limit (int): Сообщений на странице, от 1 до 200
     * - cursor (string|null): next_cursor из прошлого ответа; без него первая страница
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{include_notes?: bool, since?: string|null, limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function listMessages(string $conversationId, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/conversations/' . rawurlencode($conversationId) . '/messages', $query, null, $options, 'json');
    }

    /**
     * Все страницы conversations.list_messages подряд: обходит next_cursor сам.
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{include_notes?: bool, since?: string|null, limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{channel: string, contact: array<string, mixed>, conversation_id?: string|null, direction: 'in'|'out', id: string, interactive?: array<string, mixed>|null, is_deleted?: bool, is_note?: bool, media_url?: string|null, sender: array<string, mixed>, session_id: string, status?: string|null, template?: array<string, mixed>|null, text?: string, timestamp?: string|null, type: string}>
     */
    public function listMessagesAll(string $conversationId, array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/conversations/' . rawurlencode($conversationId) . '/messages', $query, $options);
    }

    /**
     * Задать метки диалога
     *
     * Задаёт метки диалога. Список **заменяет прежний целиком**: чтобы добавить метку, прочитайте текущие `tags` из
     * GET /conversations/{conversation_id} и передайте их вместе с новой; `[]` убирает все. Метки копируются в
     * карточку контакта, как в чате. В ответе метки в поле `tags`.
     *
     * Отдельного вебхука о метках нет: изменение видно по `updated_at` диалога. Ошибки: 403 `FORBIDDEN`, 404
     * `conversation_not_found`. Право ключа: `conversations:write`.
     *
     * Поля тела запроса:
     * - labels (list<string>, обязательно): Все метки диалога после изменения, до 100, например ["VIP", "доставка"].
     *   Список заменяет прежний целиком: чтобы добавить метку, передайте старые и новую. [] убирает все. Повторы и
     *   пустые строки отбрасываются
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{labels: list<string>} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{assignee?: array<string, mixed>|null, channel: string, contact: array<string, mixed>, created_at?: string|null, display_id: int, id: string, is_group?: bool, last_message: array<string, mixed>, phone_number_id?: string|null, session_id: string, snoozed_until?: string|null, status: 'open'|'pending'|'snoozed'|'resolved', tags?: list<string>, unread_count?: int, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function setLabels(string $conversationId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/conversations/' . rawurlencode($conversationId) . '/labels', $query, $body, $options, 'json');
    }

    /**
     * Сменить статус диалога
     *
     * Меняет статус диалога: `open` (открыт, нужно ответить), `pending` (ожидает: например, ответа клиента или
     * решения коллеги), `snoozed` (отложен до `snoozed_until`), `resolved` (решён).
     *
     * Закрыть диалог: `{"status": "resolved"}`. Как в чате, клиенту уходит запрос оценки качества, если он включён в
     * настройках, и бот снова отвечает в этом диалоге. Новое сообщение клиента откроет решённый или отложенный
     * диалог само. Уходит вебхук `conversation.status_changed` (если статус действительно изменился).
     *
     * Ошибки: 403 `FORBIDDEN` (у сотрудника только просмотр), 404 `conversation_not_found`. Право ключа:
     * `conversations:write`.
     *
     * Поля тела запроса:
     * - snoozed_until (string|null): До какого времени отложить, ISO 8601 с часовым поясом, например
     *   2026-10-20T09:00:00+05:00. Если передано, статус становится snoozed
     * - status ('open'|'pending'|'snoozed'|'resolved', обязательно): Новый статус. Статус диалога: open (открыт,
     *   менеджеру нужно ответить), pending (ожидает: ответ дан, ждём клиента или другой отдел), snoozed (отложен до
     *   snoozed_until), resolved (решён, закрыт). Новое сообщение клиента снова открывает решённый и отложенный
     *   диалог
     *
     * @param string $conversationId id диалога: поле id из GET /api/v2/external/conversations или conversation_id из вебхука message.incoming, например 6a7d5d3712f8e1a5c19f40c4
     * @param array{snoozed_until?: string|null, status: 'open'|'pending'|'snoozed'|'resolved'} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $params настройки вызова
     * @return array{assignee?: array<string, mixed>|null, channel: string, contact: array<string, mixed>, created_at?: string|null, display_id: int, id: string, is_group?: bool, last_message: array<string, mixed>, phone_number_id?: string|null, session_id: string, snoozed_until?: string|null, status: 'open'|'pending'|'snoozed'|'resolved', tags?: list<string>, unread_count?: int, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function setStatus(string $conversationId, array $body, array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('POST', '/conversations/' . rawurlencode($conversationId) . '/status', $query, $body, $options, 'json');
    }
}
