<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** Ответа нет: сеть недоступна. SDK уже повторил запрос. Код CONNECTION_ERROR. */
class ConnectionException extends WazzapoException
{
    public function __construct(string $message, ?\Throwable $previous = null, string $errorCode = 'CONNECTION_ERROR')
    {
        parent::__construct($message, null, $errorCode, null, null, [], $previous);
    }
}
