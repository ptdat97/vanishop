<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;

/**
 * Extension point (tag `vani.promotion.rules`): điều kiện của khuyến mãi — BxGy, đơn đầu tiên, theo bộ sưu tập…
 * Chỉ đọc, không ghi DB, không I/O mạng (chạy mỗi lần xem giỏ/checkout).
 */
interface PromotionRule
{
    public const TAG = 'vani.promotion.rules';

    public function type(): string;

    public function label(): string;

    /**
     * @param  array<string, mixed>  $config
     * @return list<string> lỗi cấu hình (rỗng = hợp lệ) — dùng khi lưu khuyến mãi trong Admin
     */
    public function validateConfig(array $config): array;

    /**
     * @param  array<string, mixed>  $config
     * @param  Eligibility  $candidates  dòng đang xét (đã lọc theo brand và các rule trước)
     */
    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility;
}
