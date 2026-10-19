<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы tasks: tasks.create, tasks.list, tasks.update. */
final class Tasks
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Поставить задачу по сделке
     *
     * Ставит задачу по сделке: перезвонить, встретиться, отправить смету.
     *
     * due_at в ISO 8601: Казахстан живёт по UTC+5, «завтра в 10:00 по Астане» это 05:00Z
     * (2026-10-19T05:00:00Z) или 2026-10-19T10:00:00+05:00. Исполнитель по умолчанию
     * ответственный сделки, другой: id из GET /team. Исполнитель получит уведомление и
     * напоминание, уходит вебхук task.created. Повтор с тем же Idempotency-Key задачу не дублирует.
     * Право tasks:write. Ошибки: 404 deal_not_found, 422 responsible_user_not_found.
     *
     * Поля тела запроса:
     * - description (string): Подробности, до 5000 знаков
     * - due_at (string, обязательно): Срок в ISO 8601. Лучше в UTC с Z: Казахстан живёт по UTC+5, поэтому «завтра в
     *   10:00 по Астане» = 05:00Z, например 2026-10-19T05:00:00Z. Можно с поясом: 2026-10-19T10:00:00+05:00. Время
     *   без пояса считается UTC
     * - responsible_user_id (string|null): Исполнитель: id сотрудника строкой: поле id из GET /team, например "12".
     *   Не передали: ответственный сделки
     * - task_type (string): Тип задачи: системные call (звонок), meeting (встреча), email (письмо), task (дело) или
     *   ключ своего типа вида c_6713d0a1b2c3. Списка своих типов в API нет: их заводят и видят в кабинете,
     *   «Настройки задач», а ключ виден в task_type задач этого типа (GET /deals/{deal_id}/tasks). Неизвестный или
     *   скрытый тип: 400 BAD_REQUEST
     * - title (string, обязательно): Что сделать, 1–300 знаков
     *
     * @param string $dealId id сделки: поле id из GET /deals или из ответа POST /deals, например 6713b1d5f2c3d4e5f6071829
     * @param array{description?: string, due_at: string, responsible_user_id?: string|null, task_type?: string, title: string} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{completed_at?: string|null, created_at?: string|null, deal_id?: string|null, description?: string, done?: bool, due_at?: string|null, id: string, responsible_user_id?: string|null, status: 'open'|'done'|'cancelled', task_type?: string, title: string, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function create(string $dealId, array $body, array $options = []): array
    {
        return $this->http->request('POST', '/deals/' . rawurlencode($dealId) . '/tasks', [], $body, $options, 'json');
    }

    /**
     * Задачи сделки
     *
     * Задачи сделки по сроку, ближайшие первыми, вместе с выполненными (done=true).
     *
     * Срок due_at в UTC с Z: 05:00Z это 10:00 по Астане. Ключ сотрудника без права видеть чужие
     * задачи получит только свои. Право tasks:read. Нет сделки: 404 deal_not_found.
     *
     * Параметры запроса:
     * - limit (int): Сколько задач на странице, от 1 до 200, по умолчанию 50
     * - cursor (string|null): next_cursor из прошлого ответа; для первой страницы не передавайте
     *
     * @param string $dealId id сделки: поле id из GET /deals или из ответа POST /deals, например 6713b1d5f2c3d4e5f6071829
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(string $dealId, array $params = [], array $options = []): array
    {
        return $this->http->request('GET', '/deals/' . rawurlencode($dealId) . '/tasks', $params, null, $options, 'json');
    }

    /**
     * Все страницы tasks.list подряд: обходит next_cursor сам.
     *
     * @param string $dealId id сделки: поле id из GET /deals или из ответа POST /deals, например 6713b1d5f2c3d4e5f6071829
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return \Generator<int, array{completed_at?: string|null, created_at?: string|null, deal_id?: string|null, description?: string, done?: bool, due_at?: string|null, id: string, responsible_user_id?: string|null, status: 'open'|'done'|'cancelled', task_type?: string, title: string, updated_at?: string|null}>
     */
    public function listAll(string $dealId, array $params = [], array $options = []): \Generator
    {
        return $this->http->paginate('/deals/' . rawurlencode($dealId) . '/tasks', $params, $options);
    }

    /**
     * Изменить задачу
     *
     * Меняет название, срок или исполнителя задачи, отмечает её выполненной.
     *
     * Меняются только переданные поля. done=true отмечает выполненной, как кнопка «Выполнено»
     * в кабинете, done=false возвращает в работу. id задачи из GET /deals/{deal_id}/tasks.
     * Право tasks:write. Ошибки: 404 task_not_found, 422 responsible_user_not_found.
     *
     * Поля тела запроса:
     * - done (bool|null): true: отметить выполненной (как кнопка «Выполнено»), false: вернуть в работу
     * - due_at (string|null): Новый срок. Срок в ISO 8601. Лучше в UTC с Z: Казахстан живёт по UTC+5, поэтому
     *   «завтра в 10:00 по Астане» = 05:00Z, например 2026-10-19T05:00:00Z. Можно с поясом:
     *   2026-10-19T10:00:00+05:00. Время без пояса считается UTC
     * - responsible_user_id (string|null): Новый исполнитель: id сотрудника строкой: поле id из GET /team, например
     *   "12"
     * - title (string|null): Новое название, 1–300 знаков
     *
     * @param string $taskId id задачи: поле id из GET /deals/{deal_id}/tasks или из ответа POST /deals/{deal_id}/tasks
     * @param array{done?: bool|null, due_at?: string|null, responsible_user_id?: string|null, title?: string|null} $body
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{completed_at?: string|null, created_at?: string|null, deal_id?: string|null, description?: string, done?: bool, due_at?: string|null, id: string, responsible_user_id?: string|null, status: 'open'|'done'|'cancelled', task_type?: string, title: string, updated_at?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function update(string $taskId, array $body, array $options = []): array
    {
        return $this->http->request('PATCH', '/tasks/' . rawurlencode($taskId), [], $body, $options, 'json');
    }
}
