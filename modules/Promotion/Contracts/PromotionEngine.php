<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Shared\Domain\Money\Money;

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

    /**
     * Kiểm tra lại (0.3.31) các khuyến mãi đã áp cho một đơn trên phần hàng còn lại, khi khách bớt hàng. Chỉ đánh giá lại
     * rule `CartContentRule` (rule khác coi như vẫn đạt) rồi áp lại action theo đúng thứ tự đã áp; không xét trạng thái,
     * lịch chạy, lượt dùng, ngân sách hiện tại (đã ghi nhận lúc đặt). Khuyến mãi không kiểm tra lại được (đã xoá, rule/
     * action thuộc plugin đang tắt) không có trong kết quả → nơi gọi giữ nguyên giảm giá của nó.
     *
     * @param  list<int>  $promotionIds  theo thứ tự đã áp lúc đặt hàng
     * @return array<int, array<int, Money>> promotion id => (khoá dòng => giảm giá mới; dòng không còn được giảm thì vắng)
     */
    public function recheck(PromotionContext $context, array $promotionIds): array;

    /**
     * Cập nhật giảm giá thực còn của từng khuyến mãi trên đơn sau huỷ một phần (0.3.31): `discount_amount` của lượt dùng
     * = số mới, ngân sách đã dùng giảm phần chênh. Chỉ giảm, không tăng. Phải chạy TRONG transaction của thao tác trên
     * đơn. Lượt dùng/voucher giữ nguyên (đơn vẫn còn).
     *
     * @param  array<int, int>  $currentDiscounts  promotion id => giảm giá còn lại trên đơn
     */
    public function adjustUsage(int $orderId, array $currentDiscounts): void;
}
