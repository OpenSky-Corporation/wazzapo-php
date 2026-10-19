<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 422 outside_24h_window: прошло больше 24 часов с последнего сообщения клиента, отправьте шаблон. */
class OutsideWindowException extends UnprocessableEntityException
{
}
