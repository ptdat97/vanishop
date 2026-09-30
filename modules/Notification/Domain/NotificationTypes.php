<?php

declare(strict_types=1);

namespace Modules\Notification\Domain;

/**
 * Loại tin giao dịch của Core và biến có sẵn cho template. Plugin có thể gửi loại riêng qua `Notifier`.
 */
final class NotificationTypes
{
    public const ORDER_VARIABLES = ['brand_name', 'customer_name', 'order_number', 'total'];

    /**
     * @return array<string, array{label: string, variables: list<string>}>
     */
    public static function all(): array
    {
        return [
            'order_placed' => ['label' => 'Đặt đơn thành công', 'variables' => self::ORDER_VARIABLES],
            'order_cancelled' => ['label' => 'Đơn đã huỷ', 'variables' => self::ORDER_VARIABLES],
            'shipment_shipped' => ['label' => 'Đơn đang được giao', 'variables' => [...self::ORDER_VARIABLES, 'carrier', 'tracking_number']],
            'shipment_delivered' => ['label' => 'Đã giao thành công', 'variables' => [...self::ORDER_VARIABLES, 'carrier', 'tracking_number']],
        ];
    }
}
