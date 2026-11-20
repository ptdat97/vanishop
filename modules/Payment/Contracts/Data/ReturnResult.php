<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Kết quả cổng đọc Return URL (`HandlesReturn`): khoản thanh toán (public id Core đã gửi) và kết quả hiển thị cho khách.
 * `paymentPublicId` null = tham số trả về không hợp lệ (sai chữ ký…).
 */
#[PluginApi]
final readonly class ReturnResult
{
    public function __construct(
        public ?string $paymentPublicId,
        public GatewayPaymentStatus $status = GatewayPaymentStatus::Pending,
    ) {}

    public static function invalid(): self
    {
        return new self(null);
    }
}
