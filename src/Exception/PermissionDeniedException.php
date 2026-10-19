<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 403: у ключа нет права, IP не разрешён или Meta ограничила номер. */
class PermissionDeniedException extends ApiException
{
}
