<?php

declare(strict_types=1);

namespace Modules\Storefront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Application\Storefront\AccountPages;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Shared\Http\RateLimits;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Blocks\BrandGridBlock;
use Modules\Storefront\Application\Blocks\HeroBlock;
use Modules\Storefront\Application\Blocks\ProductGridBlock;
use Modules\Storefront\Application\Blocks\RichTextBlock;
use Modules\Storefront\Application\CustomerSessionState;
use Modules\Storefront\Application\Links\BrandLinkType;
use Modules\Storefront\Application\Links\CategoryLinkType;
use Modules\Storefront\Application\Links\CollectionLinkType;
use Modules\Storefront\Application\Links\LinkRegistry;
use Modules\Storefront\Application\Links\ProductLinkType;
use Modules\Storefront\Application\Links\RouteLinkType;
use Modules\Storefront\Application\Links\UrlLinkType;
use Modules\Storefront\Application\Menus\MenuPresenter;
use Modules\Storefront\Application\PopularSearches;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Application\Theme\ThemeSettings;
use Modules\Storefront\Contracts\LinkResolver;
use Modules\Storefront\Contracts\LinkType;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\Contracts\ProductCards;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Storefront\Http\LocalizedPaths;
use Modules\Storefront\Http\Middleware\PublicPageCache;
use Modules\Storefront\Http\Middleware\RequireVisiblePrices;
use Modules\Storefront\Http\Middleware\StorefrontMaintenance;
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
        $this->app->bind(ProductCards::class, ProductViews::class);
        // Không chia sẻ instance: ghi nhớ phân giải link/menu chỉ trong một lượt render (một instance), không sống qua
        // request — kể cả khi tiến trình phục vụ nhiều request (Octane, queue) — để đổi slug thấy ngay.
        $this->app->bind(LinkResolver::class, LinkRegistry::class);
        $this->app->bind(PageSeo::class, PageAlternates::class);
        $this->app->singleton(Themes::class, fn ($app): Themes => new Themes(
            (string) config('vanishop.storefront.themes_path'),
            $app->make(Settings::class),
            (string) config('vanishop.storefront.theme', Themes::BASE),
        ));
    }

    public function boot(Router $router, PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Extensions::class)->tag([HeroBlock::class, ProductGridBlock::class, BrandGridBlock::class, RichTextBlock::class], StorefrontBlock::TAG);
        $this->app->make(Extensions::class)->tag([CategoryLinkType::class, CollectionLinkType::class, BrandLinkType::class, ProductLinkType::class, RouteLinkType::class, UrlLinkType::class], LinkType::TAG);
        $permissions->register('storefront.manage', 'Sửa giao diện cửa hàng (trang chủ, menu, đầu & chân trang)');
        $navigation->group('content', 'Nội dung & giao diện', 590);
        $navigation->add('storefront', 'Giao diện', 'admin.storefront.home', 'storefront.manage', 600, group: 'content');

        $router->aliasMiddleware('vani.theme', UseActiveTheme::class);
        $router->aliasMiddleware('vani.page-cache', PublicPageCache::class);
        // Thêm vào giỏ từ trang đã cache CDN (không có token CSRF trong HTML, storefront §5), mọi ngôn ngữ. An toàn: cookie
        // phiên SameSite=Lax nên form chéo trang không mang phiên theo — không đụng được giỏ của nạn nhân.
        // Beacon lượt xem PDP (§3.5-2): navigator.sendBeacon không gửi được header CSRF.
        PreventRequestForgery::except(['_vani/xem']);
        PreventRequestForgery::except(array_map(
            fn (string $locale): string => trim(LocalizedPaths::prefix($locale).'/'.LocalizedPaths::path('cart', $locale), '/'),
            StoreLocale::supported(),
        ));
        $router->aliasMiddleware('vani.maintenance', StorefrontMaintenance::class);
        $router->aliasMiddleware('vani.prices-visible', RequireVisiblePrices::class);
        View::composer('theme::layouts.app', fn ($view) => $view->with([
            'menus' => $this->app->make(MenuPresenter::class),
            'seo' => $this->app->make(PageAlternates::class)->tags(),
            // Đầu/chân trang theo schema theme (§3.16-3): $themeSettings['footer']['copyright']…
            'themeSettings' => $this->app->make(ThemeSettings::class)->values(App::getLocale()),
            'popularSearches' => PopularSearches::from($this->app->make(Settings::class)),
            // Không CDN (mặc định): phần riêng của khách render sẵn; có CDN thì JS lấy qua /_vani/phien (storefront §5).
            'customerState' => request()->attributes->has(PublicPageCache::ATTRIBUTE) ? null : $this->app->make(CustomerSessionState::class)->for(request(), App::getLocale()),
        ]));
        View::composer('theme::pages.search', fn ($view) => $view->with('popularSearches', PopularSearches::from($this->app->make(Settings::class))));
        View::composer('theme::partials.account-menu', fn ($view) => $view->with('accountMenu', $this->app->make(AccountPages::class)->active()));
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
        $settings->define(new SettingDefinition(
            'core', 'storefront.maintenance', 'Bảo trì cửa hàng', 'bool', false,
            help: 'Bật: storefront và Storefront API trả 503. IPN thanh toán, webhook vận chuyển, Integration API, /health vẫn chạy. Nhân viên xem trước qua Admin → Giao diện → "Xem trang" (cookie 12 giờ). Trang đã cache ở CDN còn hiện tới khi hết TTL — xoá cache CDN sau khi đổi.',
        ));
        $settings->define(new SettingDefinition(
            'core', 'storefront.maintenance_message', 'Thông báo bảo trì', 'text',
            help: 'Nội dung trang bảo trì. Để trống: dùng câu mặc định của theme.',
            rules: ['max:1000'],
        ));
        $settings->define(new SettingDefinition(
            'core', PriceVisibility::SETTING, 'Hiện giá', 'select', PriceVisibility::PUBLIC,
            options: [PriceVisibility::PUBLIC => 'Mọi người', PriceVisibility::MEMBERS => 'Chỉ khách đã đăng nhập'],
            help: '"Chỉ khách đã đăng nhập": khách chưa đăng nhập thấy "Đăng nhập để xem giá", không thêm giỏ/đặt hàng được; Storefront API trả giá null, JSON-LD không có giá. Trang đã cache ở CDN còn bản cũ tới khi hết TTL — xoá cache CDN sau khi đổi.',
        ));

        $settings->define(new SettingDefinition(
            'core', 'storefront.per_page', 'Số sản phẩm mỗi trang', 'int', 24,
            help: 'Trang danh mục, thương hiệu, tìm kiếm và Storefront API khi không truyền per_page (12–60).',
            rules: ['min:12', 'max:60'],
        ));
        $settings->define(new SettingDefinition(
            'core', PopularSearches::SETTING, 'Từ khoá tìm kiếm nổi bật', 'text',
            help: 'Mỗi dòng một từ khoá (tối đa 10): gợi ý ở ô tìm kiếm và trang tìm kiếm khi chưa gõ.',
            rules: ['max:1000'],
        ));

        // Tạo giỏ không cần đăng nhập → giới hạn riêng, chặt hơn, chống spam bảng carts.
        // Tra cứu đơn bằng số đơn + SĐT: chặn dò số điện thoại.
        RateLimiter::for('vani-order-track', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.order_track', 10)));
        RateLimiter::for('vani-checkout', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.checkout', 20)));
        RateLimiter::for('vani-cart-create', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.cart_create', 30)));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->loadWebRoutes($this->modulePath('Http/routes/storefront-web.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('storefront', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
