<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Tenancy\Contracts\Settings;

/**
 * Ngưỡng "sắp hết hàng": số bán được (ATS) ≤ ngưỡng thì Admin tô cảnh báo và storefront báo "sắp hết hàng".
 * Cấu hình cửa hàng `core`/`inventory.low_stock_threshold` (Admin → Cấu hình); chưa đặt → `VANI_LOW_STOCK_THRESHOLD`.
 */
final class LowStockThreshold
{
    public const SETTING = 'inventory.low_stock_threshold';

    public function __construct(private readonly Settings $settings) {}

    public function value(): int
    {
        return max(0, (int) $this->settings->get('core', self::SETTING, (int) config('vanishop.inventory.low_stock_threshold', 3)));
    }
}
