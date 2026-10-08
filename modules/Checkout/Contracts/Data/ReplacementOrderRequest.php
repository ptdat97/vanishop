<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

/**
 * Yêu cầu tạo đơn thay thế cho đơn gốc (đổi hàng, 0.3.32). Người nhận, địa chỉ, phương thức giao lấy từ đơn gốc; phí
 * giao 0 (shop chịu); không áp khuyến mãi. Thanh toán: COD với số tiền khách phải bù (có thể 0).
 *
 * @param  list<ReplacementLine>  $lines
 */
final readonly class ReplacementOrderRequest
{
    /**
     * @param  list<ReplacementLine>  $lines
     */
    public function __construct(
        public int $parentOrderId,
        public string $reference,
        public array $lines,
    ) {}
}
