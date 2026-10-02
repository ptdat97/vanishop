<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Ordering\Contracts\Data\PlacedOrder;
use Modules\Payment\Contracts\Data\PaymentView;
use Modules\Shared\Domain\Money\Money;

/**
 * Service contract của Payment cho Checkout, Storefront, Admin.
 */
interface Payments
{
    /**
     * @return list<array{code: string, label: string}>
     */
    public function availableMethods(Money $amount): array;

    /**
     * Thời gian chờ thanh toán của cổng (giây); null = không hết hạn (COD). Checkout dùng để giữ hàng.
     */
    public function paymentTtl(string $gatewayCode): ?int;

    /**
     * Cổng thu tiền khi giao hàng (`GatewayCapabilities::collectsOnDelivery`) — Core không suy từ mã cổng.
     */
    public function collectsOnDelivery(string $gatewayCode): bool;

    /**
     * Tạo payment cho đơn — TRONG transaction PlaceOrder. Trả thời gian giữ hàng (giây, null = không hết hạn).
     *
     * @return array{public_id: string, ttl: int|null}
     */
    public function createForOrder(PlacedOrder $order, string $gatewayCode): array;

    /**
     * Khởi tạo với cổng — SAU commit. Idempotent. Lỗi cổng không làm mất đơn: trả view kèm actionError.
     */
    public function initiate(string $paymentPublicId): PaymentView;

    public function view(string $paymentPublicId): ?PaymentView;

    /**
     * Nhân viên xác nhận đã nhận tiền (chuyển khoản thủ công). Idempotent.
     */
    public function confirmManually(int $paymentId, string $note): void;

    /**
     * Hoàn tiền (một phần hoặc toàn bộ). Idempotent theo $idempotencyKey. Tổng hoàn ≤ số đã thu.
     */
    public function refund(int $paymentId, Money $amount, string $reason, string $idempotencyKey): void;

    /**
     * Hoàn tiền theo đơn (đổi/trả): chọn payment đã thu của đơn. Idempotent theo $idempotencyKey.
     *
     * @throws PaymentRejected nếu đơn chưa có khoản đã thu đủ để hoàn
     */
    public function refundOrder(int $orderId, Money $amount, string $reason, string $idempotencyKey): void;
}
