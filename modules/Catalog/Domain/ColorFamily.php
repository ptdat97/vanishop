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
     * Nhóm màu của một mã hex — tạo nhanh màu mới mà không phải chọn nhóm lọc. Theo HSL: rất sáng → trắng, rất tối → đen, ít bão
     * hoà → xám (be nếu ấm và sáng), còn lại theo sắc độ (đỏ, cam/nâu, vàng/be, xanh lá, xanh dương, tím, hồng). Hex sai dạng → null.
     */
    public static function nearest(string $hex): ?self
    {
        if (preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', $hex, $match) !== 1) {
            return null;
        }
        [$red, $green, $blue] = array_map(fn (string $part): float => hexdec($part) / 255, array_slice($match, 1));
        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;
        $delta = $max - $min;
        $saturation = $delta == 0.0 ? 0.0 : $delta / (1 - abs(2 * $lightness - 1));
        $hue = match (true) {
            $delta == 0.0 => 0.0,
            $max === $red => 60 * fmod(($green - $blue) / $delta + 6, 6),
            $max === $green => 60 * (($blue - $red) / $delta + 2),
            default => 60 * (($red - $green) / $delta + 4),
        };

        return match (true) {
            $lightness >= 0.93 => self::White,
            $lightness <= 0.12 => self::Black,
            $saturation < 0.15 => $lightness >= 0.8 ? self::White : ($lightness <= 0.2 ? self::Black : self::Grey),
            $hue >= 20 && $hue < 65 && $lightness >= 0.75 && $saturation < 0.7 => self::Beige,
            $hue >= 15 && $hue < 45 && $lightness < 0.4 => self::Brown,
            $hue < 15 || $hue >= 345 => $lightness >= 0.75 ? self::Pink : self::Red,
            $hue < 40 => self::Orange,
            $hue < 70 => self::Yellow,
            $hue < 170 => self::Green,
            $hue < 255 => self::Blue,
            $hue < 315 => self::Purple,
            default => self::Pink,
        };
    }

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
