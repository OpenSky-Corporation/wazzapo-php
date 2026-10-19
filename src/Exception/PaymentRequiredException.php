<?php

declare(strict_types=1);

namespace Wazzapo\Exception;

/** 402: тариф Wazzapo не оплачен (payment_required) или Meta не может списать оплату (meta_payment_required). */
class PaymentRequiredException extends ApiException
{
}
