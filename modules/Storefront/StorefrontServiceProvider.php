<?php

declare(strict_types=1);

namespace Modules\Storefront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Tầng ghép cho storefront (Storefront API và native storefront): không có bảng riêng,
 * chỉ gọi contract của các module Core. Xem docs/14-storefront/storefront.md.
 */
final class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Storefront';
    }

    public function boot(): void
    {
        // Tạo giỏ không cần đăng nhập → giới hạn riêng, chặt hơn, chống spam bảng carts.
        RateLimiter::for('vani-checkout', fn (Request $request): Limit => Limit::perMinute(20)->by((string) $request->ip()));
        RateLimiter::for('vani-cart-create', fn (Request $request): Limit => Limit::perMinute(30)->by((string) $request->ip()));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->bootModuleResources();
    }
}
