<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Extension\Facades\Hook;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Payments;

/**
 * Phương thức thanh toán khả dụng cho đơn: các PaymentGateway có isAvailable() + filter `vani.checkout.payment_methods`.
 * Cổng nhận đủ ngữ cảnh để quyết định (số tiền, tỉnh/phường giao, phương thức giao, số món, kênh) — quote và kiểm tra lúc
 * đặt dùng cùng ngữ cảnh nên không lệch nhau.
 */
final class PaymentMethods
{
    public function __construct(private readonly Payments $payments) {}

    /**
     * @return list<array{code: string, label: string}>
     */
    public function available(Totals $totals, CheckoutRequest $request): array
    {
        $methods = $this->payments->availableMethods($this->context($totals, $request));

        $filtered = Hook::filter('vani.checkout.payment_methods', $methods, $totals);

        return array_values(array_filter(is_array($filtered) ? $filtered : $methods, fn (mixed $method): bool => is_array($method) && isset($method['code'], $method['label'])));
    }

    public function initialPaymentStatus(string $code): string
    {
        return $this->payments->collectsOnDelivery($code) ? 'cod_pending' : 'unpaid';
    }

    private function context(Totals $totals, CheckoutRequest $request): PaymentContext
    {
        $address = $request->shippingAddress ?? [];
        $text = fn (string $key): ?string => isset($address[$key]) && trim((string) $address[$key]) !== '' ? trim((string) $address[$key]) : null;

        return new PaymentContext(
            amount: $totals->grandTotal,
            provinceCode: $text('province_code'),
            wardCode: $text('ward_code'),
            shippingMethod: $totals->shipping?->code,
            shippingSource: $totals->shipping?->source,
            itemCount: array_sum(array_map(fn (TotalsLine $line): int => $line->quantity, $totals->lines)),
            channel: $request->source,
        );
    }
}
