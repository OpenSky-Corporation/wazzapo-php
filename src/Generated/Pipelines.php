<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы pipelines: pipelines.list. */
final class Pipelines
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Список воронок
     *
     * Воронки аккаунта с этапами по порядку и своими полями сделки.
     *
     * С этого метода начинают работу со сделками: отсюда берут pipeline_id (data[].id),
     * stage_id (stages[].id) и id своих полей для custom_fields (custom_fields[].id, тип и
     * варианты options). Поле с is_amount=true хранит сумму сделки amount.
     * Ключ сотрудника видит только доступные ему воронки. Право ключа deals:read, без него 403.
     * Воронки меняются редко: можно кешировать на час и перечитывать при 422 stage_not_found.
     *
     * Параметры запроса:
     * - limit (int): Сколько воронок на странице, от 1 до 200, по умолчанию 50
     * - cursor (string|null): next_cursor из прошлого ответа; для первой страницы не передавайте
     *
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return array{data: list<array<string, mixed>>, has_more?: bool, next_cursor?: string|null}
     * @throws \Wazzapo\Exception\WazzapoException
     */
    public function list(array $params = []): array
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->request('GET', '/pipelines', $query, null, $options, 'json');
    }

    /**
     * Все страницы pipelines.list подряд: обходит next_cursor сам.
     *
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{created_at?: string|null, custom_fields: list<array<string, mixed>>, id: string, is_default: bool, name: string, stages: list<array<string, mixed>>, updated_at?: string|null}>
     */
    public function listAll(array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/pipelines', $query, $options);
    }
}
