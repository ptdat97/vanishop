<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Biến thể của {@see PromotionAction} nhận ngữ cảnh giỏ (số lượng, đơn giá, sản phẩm của từng dòng): giảm theo số bộ
 * đủ món (combo), mua X tặng Y, giảm theo bậc số lượng. Core gọi `applyInContext()` thay cho `apply()` khi action
 * implement interface này, cả lúc đánh giá giỏ lẫn khi kiểm tra lại sau huỷ một phần (`PromotionEngine::recheck`).
 * Chỉ đọc, không ghi DB, không I/O mạng.
 */
#[PluginApi]
interface ContextualPromotionAction extends PromotionAction
{
    /**
     * @param  array<int, Money>  $remaining  khoá dòng đủ điều kiện => số tiền còn lại sau khuyến mãi áp trước
     * @param  array<string, mixed>  $config
     * @return array<int, Money> giảm giá (>= 0) theo dòng; Core kẹp theo `$remaining` và giá sàn
     */
    public function applyInContext(PromotionContext $context, array $remaining, array $config): array;
}
