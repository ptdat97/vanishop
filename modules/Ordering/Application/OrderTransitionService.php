<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderTransitionRejected;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Domain\FulfillmentStatus;
use Modules\Ordering\Domain\OrderStateMachine;
use Modules\Ordering\Domain\PaymentStatus;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderConfirmed;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\CurrentContext;

final class OrderTransitionService implements OrderTransitions
{
    public function __construct(private readonly CurrentContext $context) {}

    public function transition(int $orderId, OrderStatus $to, string $reason, string $source): bool
    {
        return DB::transaction(function () use ($orderId, $to, $reason, $source): bool {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            $from = $order->order_status;

            if ($from === $to) {
                return false;
            }
            if (! OrderStateMachine::can($from, $to)) {
                throw new OrderTransitionRejected($from, $to);
            }

            $order->update(['order_status' => $to, 'lock_version' => $order->lock_version + 1]);
            $this->record($order->id, 'status_changed', $from->value, $to->value, $reason, $source);

            match ($to) {
                OrderStatus::Confirmed => event(new OrderConfirmed($order->id, $order->public_id, $order->brand_id, $reason)),
                OrderStatus::Cancelled => event(new OrderCancelled($order->id, $order->public_id, $order->brand_id, (string) $order->reservation_key, $reason, $source)),
                default => null,
            };

            return true;
        });
    }

    public function can(int $orderId, OrderStatus $to): bool
    {
        $status = Order::query()->whereKey($orderId)->value('order_status');

        return $status !== null && OrderStateMachine::can($status instanceof OrderStatus ? $status : OrderStatus::from((string) $status), $to);
    }

    public function setPaymentStatus(int $orderId, string $paymentStatus, string $reason, string $source): void
    {
        if (! PaymentStatus::isValid($paymentStatus)) {
            throw new InvalidArgumentException("payment_status [{$paymentStatus}] không hợp lệ.");
        }

        DB::transaction(function () use ($orderId, $paymentStatus, $reason, $source): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->payment_status === $paymentStatus) {
                return;
            }

            $from = $order->payment_status;
            $order->update(['payment_status' => $paymentStatus, 'lock_version' => $order->lock_version + 1]);
            $this->record($order->id, 'payment_status_changed', $from, $paymentStatus, $reason, $source);
        });
    }

    public function setFulfillmentStatus(int $orderId, string $fulfillmentStatus, string $reason, string $source): void
    {
        if (! in_array($fulfillmentStatus, FulfillmentStatus::VALUES, true)) {
            throw new InvalidArgumentException("fulfillment_status [{$fulfillmentStatus}] không hợp lệ.");
        }

        DB::transaction(function () use ($orderId, $fulfillmentStatus, $reason, $source): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->fulfillment_status === $fulfillmentStatus) {
                return;
            }

            $from = (string) $order->fulfillment_status;
            $order->update(['fulfillment_status' => $fulfillmentStatus, 'lock_version' => $order->lock_version + 1]);
            $this->record($order->id, 'fulfillment_status_changed', $from, $fulfillmentStatus, $reason, $source);
        });
    }

    public function setReturnStatus(int $orderId, string $returnStatus, string $reason, string $source): void
    {
        if (! in_array($returnStatus, ['none', 'requested', 'in_progress', 'partially_returned', 'returned'], true)) {
            throw new InvalidArgumentException("return_status [{$returnStatus}] không hợp lệ.");
        }

        DB::transaction(function () use ($orderId, $returnStatus, $reason, $source): void {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->return_status === $returnStatus) {
                return;
            }

            $from = (string) $order->return_status;
            $order->update(['return_status' => $returnStatus, 'lock_version' => $order->lock_version + 1]);
            $this->record($order->id, 'return_status_changed', $from, $returnStatus, $reason, $source);
        });
    }

    public function lock(int $orderId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('OrderTransitions::lock phải chạy trong transaction.');
        }

        Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
    }

    private function record(int $orderId, string $type, ?string $from, ?string $to, string $reason, string $source): void
    {
        $actor = $this->context->has() ? $this->context->actor() : null;

        DB::table('order_events')->insert([
            'order_id' => $orderId, 'type' => $type, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason,
            'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id, 'source' => $source,
            'correlation_id' => Context::get('correlation_id'), 'data' => null, 'created_at' => now(),
        ]);
    }
}
