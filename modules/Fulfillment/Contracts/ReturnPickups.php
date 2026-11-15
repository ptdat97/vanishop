<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Service contract (EP v3 X3): Returns gọi hãng lấy hàng trả qua hãng đã giao đơn (nếu hãng hỗ trợ `ReturnPickup`).
 */
interface ReturnPickups
{
    /** Tên hãng có thể đến lấy hàng trả của đơn; null = hãng đã giao không hỗ trợ. */
    public function carrierLabel(int $orderId): ?string;

    /**
     * Đặt lấy hàng trả; trả mã vận đơn. Gọi **ngoài** transaction.
     *
     * @throws BusinessRuleViolation hãng không hỗ trợ / từ chối
     */
    public function book(int $orderId, string $returnNumber, int $itemCount): string;
}
