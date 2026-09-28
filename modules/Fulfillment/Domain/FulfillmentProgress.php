<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Domain;

/**
 * Tổng hợp orders.fulfillment_status từ các shipment của đơn.
 */
final class FulfillmentProgress
{
    /**
     * @param  list<ShipmentStatus>  $statuses
     */
    public static function orderStatus(array $statuses): string
    {
        $active = array_values(array_filter($statuses, fn (ShipmentStatus $status): bool => $status !== ShipmentStatus::Cancelled));
        if ($active === []) {
            return 'unfulfilled';
        }

        $all = fn (ShipmentStatus ...$wanted): bool => array_filter($active, fn (ShipmentStatus $status): bool => ! in_array($status, $wanted, true)) === [];
        $left = array_filter($active, fn (ShipmentStatus $status): bool => $status->hasLeftWarehouse());

        return match (true) {
            $all(ShipmentStatus::Delivered) => 'delivered',
            $all(ShipmentStatus::Returned) => 'returned_to_sender',
            count($left) === count($active) => 'shipped',
            $left !== [] => 'partially_shipped',
            default => 'allocated',
        };
    }

    /**
     * Mọi shipment còn hiệu lực đã rời kho → commit giữ hàng (trừ on_hand).
     *
     * @param  list<ShipmentStatus>  $statuses
     */
    public static function allLeftWarehouse(array $statuses): bool
    {
        $active = array_filter($statuses, fn (ShipmentStatus $status): bool => $status !== ShipmentStatus::Cancelled);

        return $active !== [] && array_filter($active, fn (ShipmentStatus $status): bool => ! $status->hasLeftWarehouse()) === [];
    }
}
