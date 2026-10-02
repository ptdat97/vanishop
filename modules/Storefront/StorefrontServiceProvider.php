<?php

declare(strict_types=1);

namespace Modules\Storefront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Http\Middleware\UseActiveTheme;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

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

    public function register(): void
    {
        $this->app->singleton(Themes::class, fn ($app): Themes => new Themes(
            (string) config('vanishop.storefront.themes_path'),
            $app->make(Settings::class),
            (string) config('vanishop.storefront.theme', Themes::BASE),
        ));
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('vani.theme', UseActiveTheme::class);
        Blade::componentNamespace('Modules\\Storefront\\View\\Components', 'vani');

        $settings = $this->app->make(Settings::class);
        $settings->define(new SettingDefinition(
            'core', 'theme', 'Giao diện (theme)', 'select', (string) config('vanishop.storefront.theme', Themes::BASE),
            options: array_map(fn ($theme): string => $theme->label, $this->app->make(Themes::class)->all()),
            help: 'Theme đang hoạt động của cửa hàng (custom/theme/*). Theme con chỉ override vài view, còn lại theo theme cha → vani-base.',
        ));
        $settings->define(new SettingDefinition(
            'core', 'theme.tokens', 'Token giao diện (JSON)', 'text',
            help: 'Ghi đè token của theme, vd. {"color-primary": "#0f766e", "radius": "0.75rem"} → CSS variables --vani-*.',
        ));

        // Tạo giỏ không cần đăng nhập → giới hạn riêng, chặt hơn, chống spam bảng carts.
        // Tra cứu đơn bằng số đơn + SĐT: chặn dò số điện thoại.
        RateLimiter::for('vani-order-track', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));
        RateLimiter::for('vani-checkout', fn (Request $request): Limit => Limit::perMinute(20)->by((string) $request->ip()));
        RateLimiter::for('vani-cart-create', fn (Request $request): Limit => Limit::perMinute(30)->by((string) $request->ip()));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->loadWebRoutes($this->modulePath('Http/routes/storefront-web.php'));
        $this->bootModuleResources();
    }
}
