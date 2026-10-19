<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 422 VALIDATION_ERROR: details это список полей [{field, message}]. */
class ValidationException extends UnprocessableException
{
}
