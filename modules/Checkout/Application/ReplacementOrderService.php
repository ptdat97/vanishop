<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Checkout\Contracts\Data\ReplacementLine;
use Modules\Checkout\Contracts\Data\ReplacementOrderRequest;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\ReplacementOrders;
use Modules\Checkout\Contracts\ReplacementUnavailable;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Inventory\Contracts\Data\ReservationLine;
use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\StockUnavailable;
use Modules\Ordering\Contracts\Data\OrderAdjustmentDraft;
use Modules\Ordering\Contracts\Data\OrderDraft;
use Modules\Ordering\Contracts\Data\OrderLineDraft;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\Data\PlacedOrder;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Payment\Contracts\Payments;
use Modules\Shared\Domain\Money\Money;

/**
 * Đơn thay thế khi đổi hàng (order §7.1): giá trị hàng trả bù vào dòng dưới dạng giảm giá `exchange_credit`. Khách phải
 * bù phần chênh → thu hộ COD; không phải bù (0đ) → không có khoản thanh toán (`payment_method = exchange`, `paid`).
 * Đơn được xác nhận ngay (shop đã duyệt đổi) → Fulfillment tạo vận đơn như đơn thường. Phí giao 0, không khuyến mãi.
 * Người nhận/địa chỉ/phương thức giao lấy từ đơn gốc.
 */
final class ReplacementOrderService implements ReplacementOrders
{
    /** Khách bù phần chênh khi nhận hàng. */
    public const PAYMENT_METHOD = 'cod';

    /** Không phải trả thêm (giá trị hàng trả đã bù đủ). */
    public const NO_PAYMENT = 'exchange';

    public function __construct(
        private readonly OrderReader $orders,
        private readonly CatalogReader $catalog,
        private readonly TaxCalculator $tax,
        private readonly InventoryReservation $inventory,
        private readonly OrderWriter $writer,
        private readonly Payments $payments,
        private readonly PaymentMethods $paymentMethods,
        private readonly OrderTransitions $transitions,
    ) {}

    public function place(ReplacementOrderRequest $request): PlacedOrder
    {
        $parent = $this->orders->find($request->parentOrderId) ?? throw new InvalidArgumentException("Không có đơn #{$request->parentOrderId}.");
        if ($request->lines === []) {
            throw new InvalidArgumentException('Đơn thay thế cần ít nhất một dòng.');
        }

        $variantIds = array_values(array_unique(array_map(fn (ReplacementLine $line): int => $line->variantId, $request->lines)));
        $variants = $this->catalog->sellableVariants($variantIds, app()->getLocale(), now()->getTimestamp());
        $missing = array_values(array_diff($variantIds, array_keys($variants)));
        if ($missing !== []) {
            throw new ReplacementUnavailable($missing, 'not_sellable');
        }

        $currency = $parent->currencyCode;
        $lines = [];
        foreach ($request->lines as $index => $line) {
            $variant = $variants[$line->variantId];
            $subtotal = Money::of($line->unitAmount * $line->quantity, $currency);
            $lines[] = new TotalsLine(
                key: $index + 1, variantId: $variant->id, brandId: $variant->brandId, styleId: $variant->styleId, sku: $variant->sku, name: $variant->name,
                colorName: $variant->colorName, sizeCode: $variant->sizeCode, imageUrl: $variant->imageUrl, quantity: $line->quantity,
                unitPrice: Money::of($line->unitAmount, $currency), compareAt: null, subtotal: $subtotal,
                discount: Money::of(min(max(0, $line->creditAmount), $subtotal->amount), $currency), brandName: $variant->brandName,
            );
        }
        $context = new TotalsContext($parent->customerId, $currency, $lines, [], [], null, $parent->shippingAddress, now()->getTimestamp());
        $taxes = $this->tax->calculate($context);
        $lines = array_map(fn (TotalsLine $line): TotalsLine => $line->withTax($taxes[$line->key]['rate_bp'] ?? 0, Money::of($taxes[$line->key]['amount'] ?? 0, $currency)), $lines);

        $publicId = (string) Str::ulid();
        $reservationKey = "order:{$publicId}";
        $quantities = [];
        foreach ($lines as $line) {
            $quantities[$line->variantId] = ($quantities[$line->variantId] ?? 0) + $line->quantity;
        }
        try {
            $this->inventory->reserve(new ReservationRequest($reservationKey, array_map(fn (int $variantId, int $quantity): ReservationLine => new ReservationLine($variantId, $quantity), array_keys($quantities), $quantities)));
        } catch (StockUnavailable $exception) {
            throw new ReplacementUnavailable([$exception->variantId], 'insufficient_stock');
        }

        $sum = fn (callable $pick): int => array_sum(array_map(fn (TotalsLine $line): int => $pick($line), $lines));
        $credit = $sum(fn (TotalsLine $line): int => $line->discount->amount);
        $total = $sum(fn (TotalsLine $line): int => $line->total()->amount);

        $method = $total > 0 ? self::PAYMENT_METHOD : self::NO_PAYMENT;
        $placed = $this->writer->create(new OrderDraft(
            publicId: $publicId,
            source: 'exchange',
            customerId: $parent->customerId,
            currencyCode: $currency,
            paymentMethod: $method,
            paymentStatus: $total > 0 ? $this->paymentMethods->initialPaymentStatus(self::PAYMENT_METHOD) : 'paid',
            lines: array_map(fn (TotalsLine $line): OrderLineDraft => new OrderLineDraft(
                $line->variantId, $line->sku, $line->name, $line->colorName, $line->sizeCode, $line->imageUrl, $line->quantity,
                $line->unitPrice->amount, null, $line->subtotal->amount, $line->discount->amount, $line->total()->amount,
                $line->taxRateBp, $line->tax->amount ?? 0, $line->brandId, $line->brandName, [], $line->styleId,
            ), $lines),
            adjustments: $credit > 0 ? [new OrderAdjustmentDraft('exchange_credit', 'core', $request->reference, "Bù giá trị hàng trả ({$request->reference})", -$credit, ['parent_order_id' => $parent->id])] : [],
            subtotalAmount: $sum(fn (TotalsLine $line): int => $line->subtotal->amount),
            discountAmount: $credit,
            shippingAmount: 0,
            taxAmount: $sum(fn (TotalsLine $line): int => $line->tax->amount ?? 0),
            totalAmount: $total,
            customer: [
                'full_name' => $parent->recipient['full_name'],
                'phone' => $parent->recipient['phone'],
                'email' => $parent->recipient['email'] ?? null,
            ],
            shippingAddress: $parent->shippingAddress,
            shippingMethod: [...$parent->shippingMethod, 'fee' => 0],
            note: "Đơn đổi hàng cho {$parent->number} ({$request->reference})",
            reservationKey: $reservationKey,
            sourceCartId: null,
            meta: ['exchange' => ['parent_order' => $parent->number, 'reference' => $request->reference]],
            parentOrderId: $parent->id,
        ));
        if ($total > 0) {
            $this->payments->createForOrder($placed, self::PAYMENT_METHOD);
        }
        $this->transitions->transition($placed->id, OrderStatus::Confirmed, 'exchange', 'system');

        return $placed;
    }
}
