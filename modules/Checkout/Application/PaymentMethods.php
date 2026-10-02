<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\Totals;
use Modules\Extension\Facades\Hook;
use Modules\Payment\Contracts\Payments;

/**
 * Phương thức thanh toán khả dụng cho đơn: các PaymentGateway có isAvailable() + filter `vani.checkout.payment_methods`.
 */
final class PaymentMethods
{
    public function __construct(private readonly Payments $payments) {}

    /**
     * @return list<array{code: string, label: string}>
     */
    public function available(Totals $totals): array
    {
        $methods = $this->payments->availableMethods($totals->grandTotal);

        $filtered = Hook::filter('vani.checkout.payment_methods', $methods, $totals);

        return array_values(array_filter(is_array($filtered) ? $filtered : $methods, fn (mixed $method): bool => is_array($method) && isset($method['code'], $method['label'])));
    }

    public function initialPaymentStatus(string $code): string
    {
        return $this->payments->collectsOnDelivery($code) ? 'cod_pending' : 'unpaid';
    }
}
