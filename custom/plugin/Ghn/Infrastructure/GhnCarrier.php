<?php

declare(strict_types=1);

namespace Plugin\Ghn\Infrastructure;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Modules\Checkout\Contracts\Data\ShippingOption;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\ShippingRateProvider;
use Modules\Fulfillment\Contracts\Data\CarrierCapabilities;
use Modules\Fulfillment\Contracts\Data\CarrierEvent;
use Modules\Fulfillment\Contracts\Data\CarrierShipment;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\InvalidCarrierEvent;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Shared\Domain\Money\Money;

/**
 * Tích hợp đối tác vận chuyển Giao Hàng Nhanh (GHN).
 * Thực thi ShippingCarrier (tạo đơn, webhook cập nhật hành trình)
 * và ShippingRateProvider (báo giá cước GHN ở checkout).
 */
final class GhnCarrier implements ShippingCarrier, ShippingRateProvider
{
    public const CODE = 'ghn';

    /** @var array<string, string> publicId => GHN tracking code */
    public static array $mockOrders = [];

    public function __construct(
        private readonly string $token = 'test-ghn-token',
        private readonly string $shopId = '123456',
        private readonly string $webhookSecret = 'ghn-webhook-secret',
        /** @var array<string, array{label: string, fee: int}> */
        private readonly array $services = [],
    ) {}

    public function code(): string
    {
        return self::CODE;
    }

    public function label(): string
    {
        return 'Giao Hàng Nhanh (GHN)';
    }

    public function capabilities(): CarrierCapabilities
    {
        return new CarrierCapabilities(
            autoBooking: true,
            webhooks: true,
            cancel: true,
        );
    }

    public function createShipment(ShipmentData $shipment): CarrierShipment
    {
        // Idempotent: gọi lại cho cùng shipment publicId trả cùng tracking number
        self::$mockOrders[$shipment->publicId] ??= 'GHN'.str_pad((string) (count(self::$mockOrders) + 10001), 8, '0', STR_PAD_LEFT);
        $trackingNumber = self::$mockOrders[$shipment->publicId];

        return new CarrierShipment(
            trackingNumber: $trackingNumber,
            labelUrl: "https://order.ghn.vn/print/a5?token={$this->token}&order_code={$trackingNumber}",
            serviceCode: 'ghn_standard',
        );
    }

    public function cancel(ShipmentData $shipment): void
    {
        // Gửi lệnh huỷ đơn sang API GHN (hoặc noop khi test)
    }

    public function parseWebhook(Request $request): CarrierEvent
    {
        $tracking = (string) $request->input('OrderCode');
        $ghnStatus = (string) $request->input('Status');
        $eventToken = (string) $request->input('Token');

        if ($tracking === '' || $ghnStatus === '' || $eventToken === '') {
            throw new InvalidCarrierEvent('Thiếu dữ liệu webhook GHN bắt buộc.');
        }

        if (! hash_equals($this->webhookSecret, $eventToken)) {
            throw new InvalidCarrierEvent('Token webhook GHN không khớp bí mật.');
        }

        $standardStatus = $this->mapGhnStatus($ghnStatus);

        return new CarrierEvent(
            trackingNumber: $tracking,
            status: $standardStatus,
            eventId: (string) ($request->input('EventId') ?: "ghn-evt-{$tracking}-".time()),
            occurredAt: new DateTimeImmutable,
            description: "GHN trạng thái: {$ghnStatus}",
            maskedPayload: [
                'OrderCode' => $tracking,
                'Status' => $ghnStatus,
            ],
            acknowledgement: ['code' => 200, 'message' => 'success'],
        );
    }

    /**
     * ShippingRateProvider: tính phí vận chuyển GHN tại checkout.
     *
     * @return list<ShippingOption>
     */
    public function options(TotalsContext $context): array
    {
        $services = $this->services !== [] ? $this->services : [
            'ghn_standard' => ['label' => 'Giao Hàng Nhanh (Tiêu chuẩn 2-3 ngày)', 'fee' => 30_000],
        ];

        $options = [];
        foreach ($services as $code => $service) {
            $options[] = new ShippingOption(
                code: $code,
                label: $service['label'],
                fee: Money::vnd($service['fee']),
                source: self::CODE,
            );
        }

        return $options;
    }

    /**
     * Map trạng thái vận đơn GHN sang giá trị chuẩn của `ShipmentStatus` trong Core
     * (created, picked_up, in_transit, out_for_delivery, failed_attempt, delivered, returning, returned, cancelled).
     * Plugin chỉ dùng giá trị chuẩn dạng chuỗi để không phụ thuộc tầng Domain của Core (rule R5).
     */
    public function mapGhnStatus(string $status): string
    {
        return match (strtolower($status)) {
            'ready_to_pick', 'picking' => 'created',
            'picked', 'storing' => 'picked_up',
            'transporting', 'sorting' => 'in_transit',
            'delivering' => 'out_for_delivery',
            'delivery_fail' => 'failed_attempt',
            'delivered' => 'delivered',
            'return' => 'returning',
            'returned' => 'returned',
            'cancel' => 'cancelled',
            default => 'in_transit',
        };
    }
}
