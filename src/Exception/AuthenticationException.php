<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 401: нет ключа, ключ неверный или отозван (INVALID_API_KEY). */
class AuthenticationException extends ApiException
{
}
