<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Payment\Contracts\PaymentGateway;

/**
 * Cổng thanh toán theo mã, từ tag `vani.payment.gateways` — chỉ cổng có hiệu lực trong phạm vi hiện tại
 * (plugin cổng tắt ở brand này thì cổng không có mặt).
 */
final class GatewayRegistry
{
    public const TAG = PaymentGateway::TAG;

    public function __construct(private readonly Extensions $extensions) {}

    public function get(string $code): ?PaymentGateway
    {
        return $this->all()[$code] ?? null;
    }

    /**
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        return $this->extensions->implementations(self::TAG, PaymentGateway::class);
    }
}
