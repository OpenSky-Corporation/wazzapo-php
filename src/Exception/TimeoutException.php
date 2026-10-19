<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** Сервер не ответил за timeout. */
class TimeoutException extends ConnectionException
{
}
