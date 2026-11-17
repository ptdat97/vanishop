<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Domain;

/**
 * Tiền thu hộ (COD) theo vận đơn: đơn đang chờ thu khi giao → toàn bộ tổng đơn thu ở vận đơn đầu tiên, các vận đơn tách
 * kho còn lại 0đ (shipper không thu trùng); đơn đã trả trước → mọi vận đơn 0đ.
 */
final class ShipmentCod
{
    /**
     * @return list<int>
     */
    public static function amounts(int $shipmentCount, bool $collectOnDelivery, int $orderTotal): array
    {
        $amounts = array_fill(0, max(0, $shipmentCount), 0);
        if ($collectOnDelivery && $amounts !== []) {
            $amounts[0] = $orderTotal;
        }

        return $amounts;
    }
}
