<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** Вебхук не прошёл проверку: нет секрета или заголовков, подпись не сошлась, время вне допуска, тело не JSON. Отвечайте 400. */
class WebhookVerificationException extends WazzapoException
{
}
