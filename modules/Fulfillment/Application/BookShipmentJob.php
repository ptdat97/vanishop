<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đặt vận đơn qua API hãng sau commit. Idempotent: shipment đã có mã thì bỏ qua.
 */
final class BookShipmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $shipmentId)
    {
        $this->onQueue('fulfillment');
    }

    public function handle(CurrentContext $context, FulfillmentService $fulfillment): void
    {
        $context->runAs(ContextScope::system('book shipment'), fn () => $fulfillment->bookAutomatically($this->shipmentId, $this->tries));
    }
}
