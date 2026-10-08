<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Checkout\Contracts\ShippingAddresses;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Ordering\Contracts\Data\LineCancellationCause;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderLine;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;

/**
 * Thao tác trên đơn ngoài luồng đặt hàng: xác nhận, huỷ, đổi địa chỉ, ghi chú. Mọi thao tác ghi order_events.
 */
final class OrderCommands
{
    public function __construct(
        private readonly OrderTransitions $transitions,
        private readonly CurrentContext $context,
        private readonly AuditLogger $audit,
        private readonly ShippingAddresses $addresses,
        private readonly InventoryReservation $inventory,
        private readonly PromotionClawback $clawback,
        private readonly PromotionEngine $promotions,
    ) {}

    public function confirm(int $orderId, string $reason): void
    {
        $this->transitions->transition($orderId, OrderStatus::Confirmed, $reason, 'staff');
        $this->audit->record('order.confirmed', 'order', $orderId, ['reason' => $reason]);
    }

    public function cancel(int $orderId, string $reason, string $source): void
    {
        DB::transaction(function () use ($orderId, $reason, $source): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            $allowed = $source === 'customer'
                ? OrderPolicy::customerCanCancel($order->order_status, (string) $order->fulfillment_status)
                : OrderPolicy::staffCanCancel($order->order_status, (string) $order->fulfillment_status);
            if (! $allowed) {
                throw OrderActionRejected::cannotCancel();
            }

            $this->transitions->transition($order->id, OrderStatus::Cancelled, $reason, $source);
        });

        if ($source === 'staff') {
            $this->audit->record('order.cancelled', 'order', $orderId, ['reason' => $reason]);
        }
    }

    /**
     * Có danh mục địa giới: mã tỉnh/phường phải hợp lệ, tên lấy theo danh mục (cùng quy tắc checkout).
     *
     * @param  array{province_code: string, province_name?: string|null, ward_code: string, ward_name?: string|null, street_line: string}  $address
     */
    public function changeShippingAddress(int $orderId, array $address, string $reason, int $expectedLockVersion): void
    {
        $address = array_map(fn (?string $value): string => trim((string) $value), $address);
        $address = $this->addresses->normalize($address) ?? throw OrderActionRejected::invalidAddress();
        if (($address['province_name'] ?? '') === '' || ($address['ward_name'] ?? '') === '') {
            throw OrderActionRejected::invalidAddress();
        }

        DB::transaction(function () use ($orderId, $address, $reason, $expectedLockVersion): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->lock_version !== $expectedLockVersion) {
                throw OrderActionRejected::stale();
            }
            if (! OrderPolicy::canChangeAddress($order->order_status, (string) $order->fulfillment_status)) {
                throw OrderActionRejected::cannotChangeAddress();
            }

            $old = $order->shipping_address;
            $order->update(['shipping_address' => $address, 'lock_version' => $order->lock_version + 1]);
            $this->event($order->id, 'address_changed', $reason, ['from' => $old, 'to' => $address]);
            $this->audit->record('order.address_changed', 'order', $order->id, ['reason' => $reason]);
        });
    }

    /**
     * Huỷ một phần (nhân viên): giảm số lượng + tiền của dòng theo tỷ lệ (giảm giá, thuế phân bổ), giảm tổng đơn, nhả hàng
     * giữ của phần huỷ, ghi adjustment `cancellation` + order_events. Sau commit: OrderLinesCancelled (dựng lại vận đơn,
     * hoàn tiền/giảm thu hộ). Phí giao giữ nguyên.
     *
     * Khách yêu cầu bớt hàng (`$cause = Customer`): phần khuyến mãi mà hàng còn lại không còn đủ điều kiện được thu hồi
     * (adjustment `promotion_clawback`), tối đa bằng số tiền phần huỷ — tiền hoàn/giảm thu hộ là phần chênh ròng.
     *
     * @param  array<int, int>  $quantities  order_line_id => số lượng huỷ
     */
    public function cancelLines(int $orderId, array $quantities, string $reason, int $expectedLockVersion, LineCancellationCause $cause = LineCancellationCause::Shop): void
    {
        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);
        if ($quantities === []) {
            throw OrderActionRejected::invalidCancelQuantities();
        }

        DB::transaction(function () use ($orderId, $quantities, $reason, $expectedLockVersion, $cause): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->lock_version !== $expectedLockVersion) {
                throw OrderActionRejected::stale();
            }
            if (! OrderPolicy::staffCanCancelLines($order->order_status, (string) $order->fulfillment_status, (string) $order->payment_status)) {
                throw OrderActionRejected::cannotCancelLines();
            }

            $lines = OrderLine::query()->where('order_id', $orderId)->lockForUpdate()->get()->keyBy('id');
            $remainingUnits = $lines->sum('quantity');
            foreach ($quantities as $lineId => $quantity) {
                $line = $lines->get($lineId);
                if ($line === null || $quantity > $line->quantity) {
                    throw OrderActionRejected::invalidCancelQuantities();
                }
                $remainingUnits -= $quantity;
            }
            if ($remainingUnits <= 0) {
                throw OrderActionRejected::invalidCancelQuantities();
            }

            $cancelled = [];
            $breakdown = [];
            $totals = ['subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0];
            $release = [];
            foreach ($quantities as $lineId => $quantity) {
                $line = $lines->get($lineId);
                // Phần của q/Q đơn vị, làm tròn xuống; phần còn lại giữ nguyên tổng chính xác.
                $share = fn (int $amount): int => intdiv($amount * $quantity, $line->quantity);
                $part = ['subtotal' => $share($line->subtotal_amount), 'discount' => $share($line->discount_amount), 'tax' => $share($line->tax_amount)];
                // Giữ đúng "thành tiền = tạm tính − giảm giá" sau làm tròn (tổng đơn = Σ dòng + phí giao).
                $part['total'] = $line->total_amount === $line->subtotal_amount - $line->discount_amount
                    ? $part['subtotal'] - $part['discount']
                    : $share($line->total_amount);
                $line->update([
                    'quantity' => $line->quantity - $quantity, 'cancelled_quantity' => $line->cancelled_quantity + $quantity,
                    'subtotal_amount' => $line->subtotal_amount - $part['subtotal'], 'discount_amount' => $line->discount_amount - $part['discount'],
                    'tax_amount' => $line->tax_amount - $part['tax'], 'total_amount' => $line->total_amount - $part['total'],
                ]);
                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $part[$key];
                }
                $release[$line->variant_id] = ($release[$line->variant_id] ?? 0) + $quantity;
                $cancelled[] = ['order_line_id' => $line->id, 'variant_id' => $line->variant_id, 'quantity' => $quantity, 'amount' => $part['total']];
                // Đủ để lập chứng từ điều chỉnh (hoá đơn/VAT): tạm tính, giảm giá, thuế của phần huỷ theo dòng.
                $breakdown[] = ['order_line_id' => $line->id, 'quantity' => $quantity, 'subtotal' => $part['subtotal'], 'discount' => $part['discount'], 'tax' => $part['tax'], 'total' => $part['total'], 'unit_amount' => $line->unit_amount, 'tax_rate_bp' => $line->tax_rate_bp];
            }

            $clawback = $cause === LineCancellationCause::Customer
                ? $this->clawback->calculate($order, $lines, $totals['total'])
                : ['total' => 0, 'lines' => [], 'promotions' => [], 'detail' => []];
            $clawbackTax = 0;
            foreach ($clawback['lines'] as $lineId => $amount) {
                $line = $lines->get($lineId);
                $total = $line->total_amount + $amount;
                // Thuế theo tỷ lệ thành tiền (đúng cho VAT đã gồm trong giá); dòng đang 0đ → tính VAT đã gồm theo thuế suất.
                $tax = $line->total_amount > 0
                    ? intdiv(2 * $line->tax_amount * $total + $line->total_amount, 2 * $line->total_amount)
                    : Money::of($total, $order->currency_code)->includedTax($line->tax_rate_bp)->amount;
                $clawbackTax += $tax - $line->tax_amount;
                $line->update([
                    'discount_amount' => $line->discount_amount - $amount, 'total_amount' => $total, 'tax_amount' => $tax,
                    'meta' => $this->clawback->lineMetaAfter($line, $clawback['detail'][$lineId] ?? []),
                ]);
            }
            $net = $totals['total'] - $clawback['total'];

            $order->update([
                'subtotal_amount' => $order->subtotal_amount - $totals['subtotal'], 'discount_amount' => $order->discount_amount - $totals['discount'] - $clawback['total'],
                'tax_amount' => $order->tax_amount - $totals['tax'] + $clawbackTax, 'total_amount' => $order->total_amount - $net,
                'lock_version' => $order->lock_version + 1,
            ]);
            $cancellationId = (string) Str::ulid();
            DB::table('order_adjustments')->insert([
                'order_id' => $order->id, 'type' => 'cancellation', 'source' => 'core', 'code' => $cancellationId, 'label' => 'Huỷ một phần: '.$reason,
                'amount' => -$totals['total'], 'meta' => json_encode(['lines' => $breakdown, 'totals' => $totals], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($clawback['total'] > 0) {
                DB::table('order_adjustments')->insert([
                    'order_id' => $order->id, 'type' => 'promotion_clawback', 'source' => 'core', 'code' => $cancellationId,
                    'label' => 'Thu hồi khuyến mãi không còn đủ điều kiện', 'amount' => $clawback['total'],
                    'meta' => json_encode(['lines' => $clawback['lines'], 'promotions' => $clawback['promotions'], 'tax' => $clawbackTax], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            // Ngân sách/lượt dùng khuyến mãi phản ánh giảm giá còn thực sự trên đơn (cả phần của hàng đã huỷ).
            $this->promotions->adjustUsage($order->id, $this->clawback->currentByPromotion($lines));
            $this->inventory->releaseQuantities((string) $order->reservation_key, $release, "order_lines_cancelled:{$cancellationId}");
            $this->event($order->id, 'lines_cancelled', $reason, [
                'cancellation_id' => $cancellationId, 'cause' => $cause->value, 'lines' => $breakdown,
                'totals' => [...$totals, 'promotion_clawback' => $clawback['total'], 'net' => $net],
            ]);
            $this->audit->record('order.lines_cancelled', 'order', $order->id, ['reason' => $reason, 'cause' => $cause->value, 'lines' => $cancelled, 'amount' => $net, 'promotion_clawback' => $clawback['total']]);

            event(new OrderLinesCancelled($order->id, $order->public_id, $cancellationId, $cancelled, $net, $reason, 'staff', $cause->value, $clawback['total']));
        });
    }

    public function addNote(int $orderId, string $note): void
    {
        $this->event($orderId, 'note', null, ['note' => $note]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function event(int $orderId, string $type, ?string $reason, array $data): void
    {
        $actor = $this->context->has() ? $this->context->actor() : null;
        DB::table('order_events')->insert([
            'order_id' => $orderId, 'type' => $type, 'from_status' => null, 'to_status' => null, 'reason' => $reason,
            'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id, 'source' => 'staff', 'correlation_id' => Context::get('correlation_id'),
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'created_at' => now(),
        ]);
    }
}
