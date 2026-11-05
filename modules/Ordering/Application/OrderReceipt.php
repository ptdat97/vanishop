<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Persistence\Models\Order;

/**
 * Khách xác nhận đã nhận hàng (reference-parity §3.11-1): khi đơn đã giao hết, hoặc đang giao quá
 * `vanishop.fulfillment.confirm_receipt_after_days` ngày. Ghi vào `orders.meta` (`received_confirmed_at`,
 * `returns_waived`) + order_events; không đổi bảng chuyển trạng thái.
 */
final class OrderReceipt
{
    public const SETTING_COMPLETE = 'ordering.complete_on_customer_confirm';

    public static function canConfirm(Order $order): bool
    {
        if ($order->order_status !== OrderStatus::Processing || isset($order->meta['received_confirmed_at'])) {
            return false;
        }
        $status = (string) $order->fulfillment_status;
        if ($status === 'delivered') {
            return true;
        }
        if (! in_array($status, ['shipped', 'partially_shipped'], true)) {
            return false;
        }
        $shippedAt = self::changedAt($order->id, $status);

        return $shippedAt !== null && $shippedAt->lt(now()->subDays((int) config('vanishop.fulfillment.confirm_receipt_after_days', 5)));
    }

    /** Lúc đơn chuyển sang trạng thái giao hàng này (order_events). */
    public static function changedAt(int $orderId, string $fulfillmentStatus): ?Carbon
    {
        $at = DB::table('order_events')->where('order_id', $orderId)->where('type', 'fulfillment_status_changed')->where('to_status', $fulfillmentStatus)->max('created_at');

        return $at === null ? null : Carbon::parse((string) $at);
    }

    /**
     * @return array{confirmed_at: ?string, returns_waived: bool, can_confirm: bool}
     */
    public static function of(Order $order): array
    {
        return [
            'confirmed_at' => isset($order->meta['received_confirmed_at']) ? (string) $order->meta['received_confirmed_at'] : null,
            'returns_waived' => (bool) ($order->meta['returns_waived'] ?? false),
            'can_confirm' => self::canConfirm($order),
        ];
    }
}
