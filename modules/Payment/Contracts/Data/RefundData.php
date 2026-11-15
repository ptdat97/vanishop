<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

/**
 * Khoản hoàn đang chờ chuyển trả (EP v3 X5, `RefundPayout`): tiền đồng, lý do, khách nhận (tên, SĐT) — thông tin tài khoản
 * ngân hàng của khách do plugin tự thu thập/lưu.
 *
 * @param  array{full_name: string, phone: string}  $customer
 */
final readonly class RefundData
{
    /**
     * @param  array{full_name: string, phone: string}  $customer
     */
    public function __construct(
        public string $refundPublicId,
        public string $paymentPublicId,
        public string $orderNumber,
        public int $amount,
        public string $currencyCode,
        public string $reason,
        public array $customer,
    ) {}
}
