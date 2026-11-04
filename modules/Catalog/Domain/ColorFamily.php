<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

/**
 * Nhóm màu chuẩn để lọc; tên màu cụ thể đặt riêng.
 */
enum ColorFamily: string
{
    case White = 'white';
    case Black = 'black';
    case Grey = 'grey';
    case Beige = 'beige';
    case Brown = 'brown';
    case Red = 'red';
    case Pink = 'pink';
    case Orange = 'orange';
    case Yellow = 'yellow';
    case Green = 'green';
    case Blue = 'blue';
    case Purple = 'purple';
    case Multi = 'multi';
    case Metallic = 'metallic';

    /**
     * Màu đại diện cho ô màu của bộ lọc storefront (`multi` dùng dải màu).
     */
    public function swatch(): string
    {
        return match ($this) {
            self::White => '#ffffff', self::Black => '#111827', self::Grey => '#9ca3af', self::Beige => '#e8dcc4', self::Brown => '#8b5e3c',
            self::Red => '#dc2626', self::Pink => '#f9a8d4', self::Orange => '#f97316', self::Yellow => '#facc15', self::Green => '#16a34a',
            self::Blue => '#2563eb', self::Purple => '#9333ea', self::Multi => 'linear-gradient(135deg, #dc2626, #facc15, #16a34a, #2563eb)',
            self::Metallic => 'linear-gradient(135deg, #d1d5db, #f9fafb, #9ca3af)',
        };
    }
}
