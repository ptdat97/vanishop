<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

use LogicException;

final class InvalidPaymentTransition extends LogicException
{
    public function __construct(public readonly PaymentStatus $from, public readonly PaymentStatus $to)
    {
        parent::__construct("Khoản thanh toán không chuyển được từ [{$from->value}] sang [{$to->value}].");
    }
}
