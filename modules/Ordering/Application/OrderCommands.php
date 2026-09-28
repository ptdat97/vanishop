<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\CurrentContext;

/**
 * Thao tác trên đơn ngoài luồng đặt hàng: xác nhận, huỷ, đổi địa chỉ, ghi chú. Mọi thao tác ghi order_events.
 */
final class OrderCommands
{
    public function __construct(
        private readonly OrderTransitions $transitions,
        private readonly CurrentContext $context,
        private readonly AuditLogger $audit,
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
     * @param  array{province_code: string, province_name: string, ward_code: string, ward_name: string, street_line: string}  $address
     * @param  array{full_name: string, phone: string}|null  $recipient
     */
    public function changeShippingAddress(int $orderId, array $address, string $reason, int $expectedLockVersion): void
    {
        DB::transaction(function () use ($orderId, $address, $reason, $expectedLockVersion): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->lock_version !== $expectedLockVersion) {
                throw OrderActionRejected::stale();
            }
            if (! OrderPolicy::canChangeAddress($order->order_status, (string) $order->fulfillment_status)) {
                throw OrderActionRejected::cannotChangeAddress();
            }

            $old = $order->shipping_address;
            $order->update(['shipping_address' => array_map('trim', $address), 'lock_version' => $order->lock_version + 1]);
            $this->event($order->id, 'address_changed', $reason, ['from' => $old, 'to' => $address]);
            $this->audit->record('order.address_changed', 'order', $order->id, ['reason' => $reason]);
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
