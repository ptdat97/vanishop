<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\Totals;
use Modules\Extension\Facades\Hook;

/**
 * Phương thức thanh toán khả dụng. Slice 6: COD theo cấu hình; khung PaymentGateway ở slice Payment.
 */
final class PaymentMethods
{
    /**
     * @return list<array{code: string, label: string}>
     */
    public function available(Totals $totals): array
    {
        $methods = array_map(
            fn (string $code): array => ['code' => $code, 'label' => __("checkout::messages.payment_{$code}")],
            (array) config('vanishop.checkout.payment_methods', ['cod']),
        );

        $filtered = Hook::filter('vani.checkout.payment_methods', $methods, $totals);

        return array_values(array_filter(is_array($filtered) ? $filtered : $methods, fn (mixed $method): bool => is_array($method) && isset($method['code'], $method['label'])));
    }

    public static function initialPaymentStatus(string $code): string
    {
        return $code === 'cod' ? 'cod_pending' : 'unpaid';
    }
}
