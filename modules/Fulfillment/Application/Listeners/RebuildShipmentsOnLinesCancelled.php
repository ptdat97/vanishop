<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Throwable;

/**
 * Huỷ một phần: vận đơn chưa rời kho được huỷ (cả ở hãng) và tạo lại theo số lượng + hàng giữ còn lại, thu hộ theo tổng
 * mới. Lỗi → log, nhân viên tạo vận đơn thủ công (đơn đã commit).
 */
final class RebuildShipmentsOnLinesCancelled
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderLinesCancelled $event): void
    {
        try {
            $this->context->runAs(ContextScope::system('rebuild shipments'), function () use ($event): void {
                $this->fulfillment->cancelOpenShipments($event->orderId, 'lines_cancelled');
                if (config('vanishop.fulfillment.auto_create', true)) {
                    $this->fulfillment->createForOrder($event->orderId);
                }
            });
        } catch (Throwable $exception) {
            Log::error('Không dựng lại được vận đơn sau huỷ một phần — nhân viên xử lý thủ công.', ['order_id' => $event->orderId, 'exception' => $exception]);
        }
    }
}
