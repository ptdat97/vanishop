<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Shared\Domain\Money\Money;

/**
 * Extension point (tag `vani.promotion.actions`): cách tính giảm giá. Core có `percent_off`, `amount_off`.
 */
interface PromotionAction
{
    public const TAG = 'vani.promotion.actions';

    public function type(): string;

    public function label(): string;

    /**
     * @param  array<string, mixed>  $config
     * @return list<string> lỗi cấu hình (rỗng = hợp lệ) — dùng khi lưu khuyến mãi trong Admin
     */
    public function validateConfig(array $config): array;

    /**
     * @param  array<int, Money>  $remaining  số tiền còn lại của các dòng đủ điều kiện
     * @param  array<string, mixed>  $config
     * @return array<int, Money> giảm giá (>= 0) theo dòng; Core sẽ kẹp theo giá sàn
     */
    public function apply(array $remaining, array $config, string $currencyCode): array;
}
