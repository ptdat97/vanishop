<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionResult;

/**
 * Service contract: đánh giá khuyến mãi (không ghi gì) và ghi nhận sử dụng (trong transaction PlaceOrder).
 */
interface PromotionEngine
{
    public function evaluate(PromotionContext $context): PromotionResult;

    /**
     * Tăng lượt dùng voucher/khuyến mãi bằng UPDATE có điều kiện; hết lượt → PromotionUnavailable.
     * Phải chạy TRONG transaction đặt hàng.
     */
    public function recordUsage(int $orderId, ?int $customerId, string $currencyCode, PromotionResult $result): void;

    /**
     * Hoàn lượt khi huỷ đơn. Idempotent theo order id.
     */
    public function revertUsage(int $orderId): void;
}
