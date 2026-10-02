<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

enum PaymentStatus: string
{
    case Pending = 'pending';
    /** Cổng đã giữ tiền, chờ thu (CapturesLater). */
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    public function hasCollected(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }
}
