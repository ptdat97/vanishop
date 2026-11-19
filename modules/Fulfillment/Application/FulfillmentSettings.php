<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
use Modules\Tenancy\Contracts\Settings;

/**
 * Cấu hình giao hàng của cửa hàng (Admin → Cài đặt → Kho & giao hàng); mặc định theo `vanishop.fulfillment.*` (.env).
 * Đọc lúc dùng — đổi có hiệu lực ngay, kể cả worker đang chạy.
 */
final class FulfillmentSettings
{
    public const AUTO_CREATE = 'fulfillment.auto_create';

    public const BOOKING = 'fulfillment.booking';

    public const DEFAULT_CARRIER = 'fulfillment.default_carrier';

    public const DEFAULT_ITEM_WEIGHT_GRAM = 'fulfillment.default_item_weight_gram';

    public function __construct(private readonly Settings $settings) {}

    public static function define(Settings $settings): void
    {
        $text = fn (string $key): string => "fulfillment::texts.settings.{$key}";
        $group = SettingGroup::Inventory;
        foreach ([
            new SettingDefinition('core', self::AUTO_CREATE, $text('auto_create'), 'bool', (bool) config('vanishop.fulfillment.auto_create', true), help: $text('auto_create_help'), group: $group),
            new SettingDefinition('core', self::BOOKING, $text('booking'), 'select', (string) config('vanishop.fulfillment.booking', 'manual'),
                options: ['manual' => $text('booking_manual'), 'on_create' => $text('booking_on_create')], help: $text('booking_help'), group: $group),
            new SettingDefinition('core', self::DEFAULT_CARRIER, $text('default_carrier'), 'select', (string) config('vanishop.fulfillment.default_carrier', 'manual'),
                optionsFromTag: ShippingCarrier::CARRIERS_TAG, help: $text('default_carrier_help'), group: $group),
            new SettingDefinition('core', self::DEFAULT_ITEM_WEIGHT_GRAM, $text('default_item_weight_gram'), 'int', (int) config('vanishop.fulfillment.default_item_weight_gram', 300),
                help: $text('default_item_weight_gram_help'), rules: ['min:1', 'max:50000'], group: $group),
        ] as $definition) {
            $settings->define($definition);
        }
    }

    /** Tự tạo vận đơn khi đơn được xác nhận. */
    public function autoCreate(): bool
    {
        return (bool) $this->settings->get('core', self::AUTO_CREATE);
    }

    /** Đặt vận đơn ở hãng có API ngay khi tạo (`on_create`) hay khi nhân viên bấm "Đẩy lên hãng" (`manual`). */
    public function bookOnCreate(): bool
    {
        return $this->settings->get('core', self::BOOKING) === 'on_create';
    }

    public function defaultCarrier(): string
    {
        return (string) $this->settings->get('core', self::DEFAULT_CARRIER);
    }

    public function defaultItemWeightGram(): int
    {
        return max(1, (int) $this->settings->get('core', self::DEFAULT_ITEM_WEIGHT_GRAM));
    }
}
