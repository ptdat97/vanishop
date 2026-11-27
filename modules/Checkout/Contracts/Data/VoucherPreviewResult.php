<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Checkout\Contracts\VoucherPreview;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Kết quả tính thử một mã ({@see VoucherPreview}). `discount` = phần giảm do chính mã này;
 * `totalDiscount` = tổng giảm của giỏ khi dùng mã này (gồm khuyến mãi tự động — mã độc quyền có thể loại khuyến mãi khác).
 * `rejection` khác null = mã không dùng được (lý do, gợi ý "mua thêm…").
 */
#[PluginApi]
final readonly class VoucherPreviewResult
{
    public function __construct(
        public string $code,
        public Money $discount,
        public Money $totalDiscount,
        public ?VoucherRejection $rejection,
    ) {}

    public function applicable(): bool
    {
        return $this->rejection === null && $this->discount->isPositive();
    }
}
