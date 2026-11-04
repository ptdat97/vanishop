<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Persistence\Models\Order;

/**
 * Listener `vani.customer.before_delete`: khách còn đơn chưa hoàn tất hoặc đổi trả đang mở thì chưa tự xoá tài khoản
 * được (tiền, hàng, hoàn tiền còn đang xử lý và cần liên hệ được khách).
 */
final class CustomerDeletionGuard
{
    private const OPEN_RETURN = ['requested', 'in_progress'];

    /**
     * @return list<array{type: string, reference: string, label: string}>
     */
    public function __invoke(int $customerId): array
    {
        $blockers = [];
        $orders = Order::query()->withoutGlobalScopes()->where('customer_id', $customerId)
            ->where(fn ($query) => $query
                ->whereNotIn('order_status', [OrderStatus::Completed->value, OrderStatus::Cancelled->value])
                ->orWhereIn('return_status', self::OPEN_RETURN))
            ->orderBy('id')
            ->get(['id', 'number', 'order_status', 'return_status']);

        foreach ($orders as $order) {
            $returnOpen = in_array($order->return_status, self::OPEN_RETURN, true);
            $blockers[] = [
                'type' => $returnOpen ? 'return' : 'order',
                'reference' => $order->number,
                'label' => __($returnOpen ? 'ordering::messages.deletion_open_return' : 'ordering::messages.deletion_open_order', ['number' => $order->number]),
            ];
        }

        return $blockers;
    }
}
