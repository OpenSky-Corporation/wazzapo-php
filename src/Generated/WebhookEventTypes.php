<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

use Wazzapo\HttpClient;

/** Методы webhook_event_types: webhook_event_types.list. */
final class WebhookEventTypes
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    /**
     * Каталог событий
     *
     * Все события, на которые можно подписаться: имя для event_types, описание и пример поля data.
     *
     * Тело запроса на ваш адрес: {"event": "deal.created", "timestamp": "...", "data": {...}};
     * заголовок X-Wazzapo-Event дублирует имя события. Новые события добавляются без смены версии API.
     * Схема каждого поля data, примеры нажатий кнопок и подпись запроса: раздел webhooks в
     * /api/v2/external/openapi.json и описание POST /webhooks.
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
        return $this->http->request('GET', '/webhook-event-types', $query, null, $options, 'json');
    }

    /**
     * Все страницы webhook_event_types.list подряд: обходит next_cursor сам.
     *
     * @param array{limit?: int, cursor?: string|null, timeout?: float, headers?: array<string, string>} $params необязательные параметры запроса (описание выше) и настройки вызова
     * @return \Generator<int, array{description: string, example?: array<string, mixed>|null, name: string}>
     */
    public function listAll(array $params = []): \Generator
    {
        [$query, $options] = HttpClient::splitOptions($params);
        return $this->http->paginate('/webhook-event-types', $query, $options);
    }
}
