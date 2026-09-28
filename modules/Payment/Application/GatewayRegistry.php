<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Contracts\Container\Container;
use Modules\Payment\Contracts\PaymentGateway;

/**
 * Cổng thanh toán theo mã, từ tag `vani.payment.gateways` (plugin tắt thì cổng của nó không có mặt).
 */
final class GatewayRegistry
{
    public const TAG = 'vani.payment.gateways';

    public function __construct(private readonly Container $container) {}

    public function get(string $code): ?PaymentGateway
    {
        return $this->all()[$code] ?? null;
    }

    /**
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        $gateways = [];
        foreach ($this->container->tagged(self::TAG) as $gateway) {
            $gateways[$gateway->code()] = $gateway;
        }

        return $gateways;
    }
}
