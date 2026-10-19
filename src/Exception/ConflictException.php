<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 409: объект уже есть или запрос с этим Idempotency-Key ещё выполняется. */
class ConflictException extends ApiException
{
}
