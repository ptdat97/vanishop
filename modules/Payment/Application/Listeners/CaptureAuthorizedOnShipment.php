<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Vận đơn rời kho → thu các khoản đang giữ tiền của đơn (`vanishop.payment.capture_on = shipped`).
 * Cổng từ chối → giữ trạng thái authorized + log; nhân viên bấm "Thu tiền" ở Admin thanh toán.
 */
final class CaptureAuthorizedOnShipment
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly CurrentContext $context,
    ) {}

    public function handle(ShipmentStatusChanged $event): void
    {
        if ($event->to !== 'picked_up' || config('vanishop.payment.capture_on', 'shipped') !== 'shipped') {
            return;
        }

        $authorized = Payment::query()->where('order_id', $event->orderId)->where('status', PaymentStatus::Authorized)->pluck('id');
        foreach ($authorized as $paymentId) {
            try {
                $this->context->runAs(ContextScope::system('capture on shipment'), fn () => $this->payments->captureAuthorized((int) $paymentId, "shipment:{$event->shipmentId}"));
            } catch (BusinessRuleViolation $exception) {
                Log::warning('Không thu được khoản giữ tiền khi vận đơn rời kho.', ['payment_id' => $paymentId, 'shipment_id' => $event->shipmentId, 'error' => $exception->getMessage()]);
            }
        }
    }
}
