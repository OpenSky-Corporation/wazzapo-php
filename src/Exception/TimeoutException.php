<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** Сервер не ответил за timeout. Код TIMEOUT. */
class TimeoutException extends ConnectionException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous, 'TIMEOUT');
    }
}
