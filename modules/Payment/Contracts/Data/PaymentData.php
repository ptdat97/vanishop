<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use DateTimeImmutable;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Khoản thanh toán gửi cổng. `returnUrl` là Return URL chung của Core (`/payments/{gateway}/return`) — cổng `HandlesReturn`
 * dùng làm địa chỉ trả khách về. `clientIp` là IP của khách lúc đặt (cổng yêu cầu cho chống gian lận), null nếu đơn tạo
 * trong Admin. `customer` là người nhận của đơn.
 *
 * @param  array{full_name: string, phone: string, email?: ?string}  $customer
 */
#[PluginApi]
final readonly class PaymentData
{
    /**
     * @param  array{full_name: string, phone: string, email?: ?string}  $customer
     */
    public function __construct(
        public string $publicId,
        public string $gatewayCode,
        public string $orderNumber,
        public Money $amount,
        public string $status,
        public ?string $gatewayReference,
        public ?DateTimeImmutable $expiresAt,
        public ?string $returnUrl = null,
        public array $customer = ['full_name' => '', 'phone' => ''],
        public ?string $clientIp = null,
        public ?string $locale = null,
    ) {}
}
