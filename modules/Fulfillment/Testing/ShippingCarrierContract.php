<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Testing;

use Closure;
use Illuminate\Http\Request;
use Modules\Fulfillment\Contracts\Data\CarrierEvent;
use Modules\Fulfillment\Contracts\Data\CarrierShipment;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\InvalidCarrierEvent;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Shared\Domain\Money\Money;

/**
 * Bộ contract test cho mọi ShippingCarrier (Core và plugin hãng vận chuyển):
 *
 *   ShippingCarrierContract::define('vani.ghn', fn () => new GhnCarrier(...),
 *       validWebhook: fn (string $tracking) => Request::create(...),
 *       tamperedWebhook: fn (string $tracking) => Request::create(...));
 *
 * Kiểm tra: mã hợp lệ; createShipment idempotent theo mã shipment; webhook đúng chữ ký → trạng thái chuẩn hoá
 * (một giá trị của ShipmentStatus) cho đúng mã vận đơn; webhook bị sửa → InvalidCarrierEvent; không hỗ trợ webhook → luôn từ chối.
 */
final class ShippingCarrierContract
{
    /**
     * @param  Closure(): ShippingCarrier  $carrier
     * @param  (Closure(string): Request)|null  $validWebhook
     * @param  (Closure(string): Request)|null  $tamperedWebhook
     */
    public static function define(string $label, Closure $carrier, ?Closure $validWebhook = null, ?Closure $tamperedWebhook = null): void
    {
        $shipment = new ShipmentData(
            publicId: '01JCONTRACTTESTSHIPMENT001', orderNumber: 'VN2610-000001', locationId: 1, serviceCode: null,
            items: [['sku' => 'LM-DR01-BLK-S', 'name' => 'Đầm lụa', 'quantity' => 1]],
            recipient: ['full_name' => 'Nguyễn Thị Lan', 'phone' => '+84912345678'],
            address: ['province_code' => '79', 'province_name' => 'TP. Hồ Chí Minh', 'ward_code' => '26734', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi'],
            codAmount: Money::vnd(330_000),
        );

        describe("ShippingCarrier contract: {$label}", function () use ($carrier, $shipment, $validWebhook, $tamperedWebhook): void {
            it('có mã ổn định và nhãn', function () use ($carrier): void {
                $instance = $carrier();
                expect($instance->code())->toMatch('/^[a-z0-9][a-z0-9_.-]*$/')->and($instance->label())->not->toBe('');
            });

            it('createShipment trả mã vận đơn và idempotent theo mã shipment', function () use ($carrier, $shipment): void {
                $instance = $carrier();
                $first = $instance->createShipment($shipment);
                $second = $instance->createShipment($shipment);

                expect($first)->toBeInstanceOf(CarrierShipment::class)
                    ->and($first->trackingNumber)->not->toBe('')
                    ->and($second->trackingNumber)->toBe($first->trackingNumber);
            });

            it('webhook: đúng chữ ký → trạng thái chuẩn hoá; bị sửa → từ chối', function () use ($carrier, $validWebhook, $tamperedWebhook): void {
                $instance = $carrier();

                if (! $instance->capabilities()->webhooks) {
                    expect(fn () => $instance->parseWebhook(Request::create('/webhook', 'POST', ['tracking' => 'X'])))->toThrow(InvalidCarrierEvent::class);

                    return;
                }

                expect($validWebhook)->not->toBeNull('Hãng có webhook phải cung cấp validWebhook cho contract test.')
                    ->and($tamperedWebhook)->not->toBeNull('Hãng có webhook phải cung cấp tamperedWebhook cho contract test.');

                $event = $instance->parseWebhook($validWebhook('TRACK123'));
                expect($event)->toBeInstanceOf(CarrierEvent::class)
                    ->and($event->trackingNumber)->toBe('TRACK123')
                    ->and(ShipmentStatus::tryFrom($event->status))->not->toBeNull()
                    ->and($event->eventId)->not->toBe('');

                expect(fn () => $instance->parseWebhook($tamperedWebhook('TRACK123')))->toThrow(InvalidCarrierEvent::class);
            });
        });
    }
}
