<?php

declare(strict_types=1);

namespace Modules\Storefront\Listeners;

use Modules\Shared\Contracts\PageCacheInvalidator;
use Modules\Storefront\Application\Maintenance;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Tenancy\Events\SettingChanged;

/**
 * Cấu hình đổi → tag cache trang (ADR-051 §4):
 *
 * | Cấu hình | Tag | Khi nào |
 * |---|---|---|
 * | `core`/`storefront.builder.{vùng}` | `builder:{vùng}` | lô kế tiếp |
 * | `core`/`storefront.menu.{vị trí}` | `menu:{vị trí}` | lô kế tiếp |
 * | `core`/`storefront.maintenance`, `storefront.price_visibility`, `theme` | toàn bộ | ngay (CDN cũng phải xoá ngay) |
 * | còn lại | `settings:{namespace}` | lô kế tiếp |
 */
final class InvalidatePageCacheOnSettingChange
{
    /** Cấu hình đổi thì khách phải thấy ngay. */
    public const IMMEDIATE = [Maintenance::SETTING, PriceVisibility::SETTING, 'theme'];

    public function __construct(private readonly PageCacheInvalidator $invalidator) {}

    public function handle(SettingChanged $event): void
    {
        if ($event->namespace === 'core') {
            if (in_array($event->key, self::IMMEDIATE, true)) {
                $this->invalidator->flush();

                return;
            }
            foreach (['storefront.builder.' => 'builder:', 'storefront.menu.' => 'menu:'] as $prefix => $tag) {
                if (str_starts_with($event->key, $prefix)) {
                    $this->invalidator->invalidate($tag.substr($event->key, strlen($prefix)));

                    return;
                }
            }
        }

        $this->invalidator->invalidate('settings:'.$event->namespace);
    }
}
