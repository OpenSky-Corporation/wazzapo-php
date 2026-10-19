<?php

declare(strict_types=1);

namespace Wazzapo\Tests;

use Wazzapo\Transport\Request;
use Wazzapo\Transport\Response;
use Wazzapo\Transport\Transport;

/** Подставной транспорт: отдаёт ответы по очереди и запоминает запросы. */
final class FakeTransport implements Transport
{
    /** @var list<Request> */
    public array $requests = [];

    /** @param list<Response|\Throwable> $queue */
    public function __construct(private array $queue)
    {
    }

    /** @param array<string, mixed>|null $json */
    public static function json(int $status, ?array $json, array $headers = []): Response
    {
        return new Response($status, array_change_key_case($headers, CASE_LOWER), $json === null ? '' : json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \LogicException('Лишний запрос: ' . $request->method . ' ' . $request->url);
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }
        return $next;
    }
}
