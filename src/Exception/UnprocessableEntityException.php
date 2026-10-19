<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 422: запрос не прошёл проверку или Meta не приняла параметры. От него ValidationException, OutsideWindowException, TemplateException. */
class UnprocessableEntityException extends ApiException
{
}
