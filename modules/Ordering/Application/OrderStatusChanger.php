<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Domain\OrderStateMachine;
use Modules\Ordering\Persistence\Models\Order;

/**
 * Đổi trạng thái đơn từ ô chọn trên thẻ "Trạng thái đơn" và thao tác hàng loạt (0.3.99) — luôn có lý do:
 *   - `normal`: đúng trình tự → lệnh thường (xác nhận, sang đang xử lý, hoàn tất khi đã giao hết, huỷ theo OrderPolicy);
 *   - `forced`: nhảy tới trước ngoài trình tự (quyền `orders.override_status`) → OrderTransitionService::force;
 *   - bị chặn (kèm lý do): lùi trạng thái, mở lại đơn đã hoàn tất/huỷ, huỷ khi hàng đã rời kho (dùng Đổi/trả).
 * Quyền: `normal` cần `orders.manage` (huỷ cần `orders.cancel`); `forced` cần `orders.override_status`.
 */
final class OrderStatusChanger
{
    public function __construct(
        private readonly OrderCommands $commands,
        private readonly OrderTransitionService $transitions,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{mode: 'normal'|'forced'|null, permission: string|null, blocked: string|null}
     */
    public function plan(Order $order, OrderStatus $to): array
    {
        $from = $order->order_status;
        $fulfillment = (string) $order->fulfillment_status;
        $blocked = fn (string $message): array => ['mode' => null, 'permission' => null, 'blocked' => $message];

        if ($from === $to) {
            return $blocked(__('ordering::messages.status_same'));
        }
        if ($to === OrderStatus::Cancelled) {
            return OrderPolicy::staffCanCancel($from, $fulfillment)
                ? ['mode' => 'normal', 'permission' => 'orders.cancel', 'blocked' => null]
                : $blocked(__('ordering::messages.status_cancel_blocked'));
        }
        if (in_array($from, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return $blocked(__('ordering::messages.status_terminal'));
        }
        if (! OrderStateMachine::canForce($from, $to)) {
            return $blocked(__('ordering::messages.status_backward'));
        }
        $normal = OrderStateMachine::can($from, $to)
            && ($to !== OrderStatus::Completed || OrderPolicy::staffCanComplete($from, $fulfillment, (string) $order->return_status));

        return $normal
            ? ['mode' => 'normal', 'permission' => 'orders.manage', 'blocked' => null]
            : ['mode' => 'forced', 'permission' => 'orders.override_status', 'blocked' => null];
    }

    /**
     * Lựa chọn cho ô chọn trạng thái: mọi trạng thái kèm chế độ, quyền cần có và lý do bị chặn.
     *
     * @return list<array{value: string, label: string, mode: 'normal'|'forced'|null, permission: string|null, blocked: string|null}>
     */
    public function options(Order $order): array
    {
        return array_map(fn (OrderStatus $status): array => [
            'value' => $status->value,
            'label' => (string) __("ordering::labels.order_status.{$status->value}"),
            ...$this->plan($order, $status),
        ], OrderStatus::cases());
    }

    /**
     * Đổi trạng thái (gọi sau khi kiểm quyền theo `plan()`); trả chế độ đã dùng.
     *
     * @return 'normal'|'forced'
     */
    public function change(Order $order, OrderStatus $to, string $reason): string
    {
        $plan = $this->plan($order, $to);
        if ($plan['mode'] === null) {
            throw OrderActionRejected::statusChangeBlocked((string) $plan['blocked']);
        }

        if ($plan['mode'] === 'forced') {
            $this->transitions->force($order->id, $to, 'override: '.$reason, 'staff');
            $this->audit->record('order.status_overridden', 'order', $order->id, ['from' => $order->order_status->value, 'to' => $to->value, 'reason' => $reason]);

            return 'forced';
        }
        match ($to) {
            OrderStatus::Cancelled => $this->commands->cancel($order->id, $reason, 'staff'),
            OrderStatus::Confirmed => $this->commands->confirm($order->id, 'staff_changed: '.$reason),
            OrderStatus::Completed => $this->commands->complete($order->id, $reason),
            default => $this->transitions->transition($order->id, $to, 'staff_changed: '.$reason, 'staff'),
        };

        return 'normal';
    }
}
