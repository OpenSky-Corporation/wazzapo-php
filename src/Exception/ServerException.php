<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 5xx: сбой Wazzapo или Meta, SDK уже повторил запрос. */
class ServerException extends ApiException
{
}
