<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Contracts\FulfillmentRejected;
use Modules\Ordering\Events\OrderConfirmed;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Throwable;

/**
 * Đơn xác nhận → tạo shipment theo SourcingStrategy (tắt bằng VANI_FULFILLMENT_AUTO_CREATE=false).
 */
final class CreateShipmentsOnConfirm
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderConfirmed $event): void
    {
        if (! config('vanishop.fulfillment.auto_create', true)) {
            return;
        }

        try {
            $this->context->runAs(ContextScope::system('create shipments'), fn () => $this->fulfillment->createForOrder($event->orderId));
        } catch (FulfillmentRejected $exception) {
            Log::warning('Không tạo được shipment khi xác nhận đơn — nhân viên xử lý thủ công.', ['order_id' => $event->orderId, 'code' => $exception->errorCode()]);
        } catch (Throwable $exception) {
            // Đơn đã commit: lỗi hãng/hạ tầng không được làm hỏng phản hồi đặt hàng. Vận đơn vẫn ở pending_booking.
            Log::error('Lỗi khi tạo/đặt vận đơn sau xác nhận đơn.', ['order_id' => $event->orderId, 'exception' => $exception]);
        }
    }
}
