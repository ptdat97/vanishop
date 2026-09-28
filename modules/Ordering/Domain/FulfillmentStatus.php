<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Giá trị hợp lệ của orders.fulfillment_status (tổng hợp từ shipment, do Fulfillment cập nhật).
 */
final class FulfillmentStatus
{
    public const VALUES = ['unfulfilled', 'allocated', 'partially_shipped', 'shipped', 'delivered', 'returned_to_sender'];
}
