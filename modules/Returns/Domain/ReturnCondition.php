<?php

declare(strict_types=1);

namespace Modules\Returns\Domain;

/**
 * Tình trạng hàng trả khi CSKH nhận: bán được → nhập lại kho (location của vận đơn đã giao); hỏng → không nhập kho.
 * Không ghi gì → coi là bán được.
 */
enum ReturnCondition: string
{
    case Sellable = 'sellable';
    case Damaged = 'damaged';

    public static function fromInput(?string $value): self
    {
        return $value === self::Damaged->value ? self::Damaged : self::Sellable;
    }

    public function restocks(): bool
    {
        return $this === self::Sellable;
    }
}
