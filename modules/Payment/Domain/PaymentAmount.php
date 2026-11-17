<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

use Modules\Shared\Domain\Money\Money;

/**
 * Tiền cổng/hãng báo về phải khớp đúng khoản thanh toán (số tiền + tiền tệ); lệch → không ghi nhận, vào "Đối soát lệch".
 */
final class PaymentAmount
{
    public static function matches(int $expected, string $currency, Money $received): bool
    {
        return $received->amount === $expected && $received->currency->code === $currency;
    }
}
