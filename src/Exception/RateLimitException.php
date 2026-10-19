<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

use Wazzapo\HttpClient;

/** 429: слишком много запросов. retryAfter(): через сколько секунд повторить. */
class RateLimitException extends ApiException
{
    public function retryAfter(): ?int
    {
        $seconds = HttpClient::parseRetryAfter($this->headers['retry-after'] ?? null);
        return $seconds === null ? null : (int) ceil($seconds);
    }
}
