<?php

declare(strict_types=1);

namespace Wazzapo\Transport;

/** Готовый HTTP-запрос: тело уже строка JSON или поля формы multipart. */
final class Request
{
    /**
     * @param array<string, string> $headers
     * @param string|array<string, mixed>|null $body строка JSON или поля multipart (CURLFile для файлов)
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers,
        public readonly string|array|null $body,
        public readonly float $timeout,
    ) {
    }
}
