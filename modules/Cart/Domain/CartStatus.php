<?php

declare(strict_types=1);

namespace Modules\Cart\Domain;

enum CartStatus: string
{
    case Active = 'active';
    /** Đã đặt hàng (Checkout). */
    case Converted = 'converted';
    /** Đã gộp vào giỏ khác (khi khách đăng nhập). */
    case Merged = 'merged';

    public function isOpen(): bool
    {
        return $this === self::Active;
    }
}
