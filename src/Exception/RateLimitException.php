<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 429: слишком много запросов. retryAfter(): через сколько секунд повторить. */
class RateLimitException extends ApiException
{
    public function retryAfter(): ?int
    {
        $value = $this->headers['retry-after'] ?? null;
        return $value !== null && is_numeric($value) ? (int) $value : null;
    }
}
