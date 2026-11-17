<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Contracts\CarrierAssignment;
use Modules\Fulfillment\Contracts\Data\CarrierAssignmentContext;
use Modules\Fulfillment\Contracts\Data\CarrierChoice;
use Modules\Ordering\Contracts\OrderReader;

/**
 * Chọn hãng cho vận đơn mới (docs/09-order/fulfillment.md §3): nhân viên chọn → quy tắc `CarrierAssignment` của plugin
 * (EP v3 X3, hãng phải đang nhận vận đơn mới) → hãng của phương thức giao khách chọn → hãng mặc định của cửa hàng.
 */
final class CarrierSelection
{
    public function __construct(
        private readonly CarrierRegistry $registry,
        private readonly OrderReader $orders,
        private readonly Extensions $extensions,
    ) {}

    /**
     * Hãng cho vận đơn mới: nhân viên chọn → quy tắc của plugin (`CarrierAssignment`, EP v3 X3) → hãng của phương thức
     * giao khách chọn → hãng mặc định. Phần tử thứ ba: lý do chọn (ghi vào lịch sử vận đơn).
     *
     * @return array{0: string, 1: ?string, 2: ?string} [carrier, dịch vụ, lý do]
     */
    public function choose(int $orderId, ?string $requested): array
    {
        if ($requested !== null) {
            return [$requested, null, null];
        }

        $order = $this->orders->find($orderId);
        $method = $order->shippingMethod ?? [];
        if ($order !== null) {
            $context = new CarrierAssignmentContext(
                $order->id, $order->number, $order->shippingAddress, $order->paymentStatus === 'cod_pending' ? $order->totalAmount : 0, $order->totalAmount,
                array_sum(array_map(fn ($line): int => $line->quantity, $this->orders->lines($orderId))), $method,
            );
            foreach ($this->extensions->implementations(CarrierAssignment::TAG, CarrierAssignment::class, fn (CarrierAssignment $rule): string => $rule::class) as $rule) {
                $choice = $this->extensions->call($rule, fn (): ?CarrierChoice => $rule->assign($context), null, 'carrier.assign');
                if ($choice instanceof CarrierChoice && $this->registry->acceptsNewShipments($choice->carrierCode)) {
                    return [$choice->carrierCode, $choice->serviceCode, $choice->reason];
                }
            }
        }

        $source = (string) ($method['source'] ?? '');
        if ($source !== '' && $this->registry->acceptsNewShipments($source)) {
            return [$source, isset($method['code']) ? (string) $method['code'] : null, null];
        }

        // Phí giao không gắn hãng (phí cố định), carrier đã tắt hoặc đang ngừng (draining) → carrier mặc định.
        return [(string) config('vanishop.fulfillment.default_carrier', 'manual'), null, null];
    }
}
