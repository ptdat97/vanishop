<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Tests\Feature\Fixtures;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Modules\Fulfillment\Contracts\Data\CarrierCapabilities;
use Modules\Fulfillment\Contracts\Data\CarrierEvent;
use Modules\Fulfillment\Contracts\Data\CarrierShipment;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\InvalidCarrierEvent;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use RuntimeException;

/**
 * Hãng giả có API đặt vận đơn + webhook ký HMAC — mô phỏng plugin như vani.ghn.
 */
final class FakeApiCarrier implements ShippingCarrier
{
    public const SECRET = 'carrier-secret';

    public static bool $failBooking = false;

    /** @var array<string, string> */
    public static array $booked = [];

    public function code(): string
    {
        return 'fake_api';
    }

    public function label(): string
    {
        return 'Hãng giả';
    }

    public function capabilities(): CarrierCapabilities
    {
        return new CarrierCapabilities(autoBooking: true, webhooks: true, cancel: true);
    }

    public function createShipment(ShipmentData $shipment): CarrierShipment
    {
        if (self::$failBooking) {
            throw new RuntimeException('Hãng đang bảo trì');
        }

        self::$booked[$shipment->publicId] ??= 'FK'.str_pad((string) (count(self::$booked) + 1), 6, '0', STR_PAD_LEFT);

        return new CarrierShipment(self::$booked[$shipment->publicId], "https://carrier.example/label/{$shipment->publicId}", $shipment->serviceCode ?? 'standard');
    }

    public function cancel(ShipmentData $shipment): void {}

    public function parseWebhook(Request $request): CarrierEvent
    {
        $fields = ['tracking' => (string) $request->input('tracking'), 'status' => (string) $request->input('status'), 'event' => (string) $request->input('event')];
        if (in_array('', $fields, true) || ! hash_equals(self::sign($fields), (string) $request->input('sig'))) {
            throw new InvalidCarrierEvent('Chữ ký không hợp lệ.');
        }

        return new CarrierEvent($fields['tracking'], $fields['status'], $fields['event'], new DateTimeImmutable, "Hãng: {$fields['status']}", ['event' => $fields['event']], ['success' => true]);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function sign(array $fields): string
    {
        return hash_hmac('sha256', "{$fields['tracking']}|{$fields['status']}|{$fields['event']}", self::SECRET);
    }

    /**
     * @return array<string, string>
     */
    public static function webhook(string $tracking, string $status, string $event): array
    {
        $fields = ['tracking' => $tracking, 'status' => $status, 'event' => $event];

        return [...$fields, 'sig' => self::sign($fields)];
    }
}
