<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

/**
 * Extension point (tag `vani.pricing.roundings`, EP v3 X4): cách làm tròn giá ngoài "tới 1.000đ" của Core — vd. "giá đẹp"
 * đuôi 9.000 (199.000, 299.000). Dùng ở hộp thoại "Áp giá…" của bảng giá. Kết quả không âm.
 */
interface PriceRounding
{
    public const TAG = 'vani.pricing.roundings';

    public function code(): string;

    public function label(): string;

    public function round(int $amount): int;
}
