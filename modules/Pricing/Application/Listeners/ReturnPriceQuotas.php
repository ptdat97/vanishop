<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Listeners;

use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Pricing\Contracts\PriceQuotas;

/** Huỷ đơn → hoàn suất giá của đơn; huỷ một phần → hoàn theo số lượng huỷ (0.5.117). */
final class ReturnPriceQuotas
{
    public function __construct(private readonly PriceQuotas $quotas) {}

    public function cancelled(OrderCancelled $event): void
    {
        $this->quotas->revert($event->orderId);
    }

    public function linesCancelled(OrderLinesCancelled $event): void
    {
        $quantities = [];
        foreach ($event->lines as $line) {
            $quantities[$line['variant_id']] = ($quantities[$line['variant_id']] ?? 0) + $line['quantity'];
        }
        $this->quotas->release($event->orderId, $quantities);
    }
}
