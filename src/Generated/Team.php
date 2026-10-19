<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы team: team.list. */
final class Team
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Сотрудники
     *
     * Владелец аккаунта и сотрудники команды: отсюда берут `id` для назначения ответственного (`user_id` в POST
     * /conversations/{conversation_id}/assign) и для упоминаний в заметках (`mention_user_ids`). Первым в списке
     * идёт владелец.
     *
     * Отключённые сотрудники тоже в списке (`is_active: false`): по ним видно прежних ответственных, но назначить на
     * них нельзя. `phone_number_ids: null` значит, что сотрудник работает во всех номерах. Почты и телефонов
     * сотрудников в ответе нет. Право ключа: `team:read`.
     *
     * Параметры запроса:
     * - limit (int): Сотрудников на странице, от 1 до 200
     * - cursor (string|null): next_cursor из прошлого ответа; без него первая страница
     *
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = [], array $options = []): array
    {
        return $this->http->request('GET', '/team', $params, null, $options, 'json');
    }

    /**
     * Все страницы team.list подряд: обходит next_cursor сам.
     *
     * @param array<string, mixed> $params необязательные параметры запроса, список выше
     * @param array{idempotency_key?: string, timeout?: float, headers?: array<string, string>} $options
     * @return \Generator<int, array{id: string, is_active: bool, name?: string|null, phone_number_ids?: list<string>|null, role: 'owner'|'ADMIN'|'AGENT'|'VIEWER'}>
     */
    public function listAll(array $params = [], array $options = []): \Generator
    {
        return $this->http->paginate('/team', $params, $options);
    }
}
