<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 403 USAGE_LIMIT_EXCEEDED: исчерпан лимит тарифа; в details current, limit и upgrade_url. */
class UsageLimitException extends PermissionDeniedException
{
}
