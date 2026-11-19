<?php

declare(strict_types=1);

namespace Modules\Storefront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AdminNavigation;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Shared\Http\RateLimits;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Blocks\BrandGridBlock;
use Modules\Storefront\Application\Blocks\HeroBlock;
use Modules\Storefront\Application\Blocks\ProductGridBlock;
use Modules\Storefront\Application\Blocks\RichTextBlock;
use Modules\Storefront\Application\Links\BrandLinkType;
use Modules\Storefront\Application\Links\CategoryLinkType;
use Modules\Storefront\Application\Links\CollectionLinkType;
use Modules\Storefront\Application\Links\LinkRegistry;
use Modules\Storefront\Application\Links\ProductLinkType;
use Modules\Storefront\Application\Links\RouteLinkType;
use Modules\Storefront\Application\Links\UrlLinkType;
use Modules\Storefront\Application\NativeCustomerSignIn;
use Modules\Storefront\Application\PopularSearches;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Application\Seo\StoreShare;
use Modules\Storefront\Application\StoreProfile;
use Modules\Storefront\Application\Theme\Theme;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Application\Theme\ThemeTranslationLoader;
use Modules\Storefront\Contracts\CustomerSignIn;
use Modules\Storefront\Contracts\LinkResolver;
use Modules\Storefront\Contracts\LinkType;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\Contracts\ProductCards;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Storefront\Http\LocalizedPaths;
use Modules\Storefront\Http\Middleware\PublicPageCache;
use Modules\Storefront\Http\Middleware\RequireCheckoutSignIn;
use Modules\Storefront\Http\Middleware\RequireVisiblePrices;
use Modules\Storefront\Http\Middleware\StorefrontMaintenance;
use Modules\Storefront\Http\Middleware\UseActiveTheme;
use Modules\Storefront\View\Breadcrumbs;
use Modules\Storefront\View\Composers\AccountMenuComposer;
use Modules\Storefront\View\Composers\ImageComposer;
use Modules\Storefront\View\Composers\LayoutComposer;
use Modules\Storefront\View\Composers\ListingComposer;
use Modules\Storefront\View\Composers\NotificationComposer;
use Modules\Storefront\View\Composers\ProductFrameComposer;
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
        $this->app->bind(CustomerSignIn::class, NativeCustomerSignIn::class);
        // Không chia sẻ instance: ghi nhớ phân giải link/menu chỉ trong một lượt render (một instance), không sống qua
        // request — kể cả khi tiến trình phục vụ nhiều request (Octane, queue) — để đổi slug thấy ngay.
        $this->app->bind(LinkResolver::class, LinkRegistry::class);
        $this->app->bind(PageSeo::class, PageAlternates::class);
        $this->app->singleton(Themes::class, fn (Application $app): Themes => new Themes(
            (string) config('vanishop.storefront.themes_path'),
            $app->make(Settings::class),
            (string) config('vanishop.storefront.theme', Themes::BASE),
        ));
        // Chuỗi dịch `theme::` theo chuỗi theme cha → con (ADR-049).
        $this->app->extend('translation.loader', fn (Loader $loader, Application $app): ThemeTranslationLoader => new ThemeTranslationLoader(
            $loader,
            fn (): array => array_map(fn (Theme $theme): string => "{$theme->path}/lang", array_reverse($app->make(Themes::class)->chain())),
        ));
    }

    public function boot(Router $router, PermissionRegistry $permissions, AdminNavigation $navigation): void
    {
        $this->app->make(Extensions::class)->tag([HeroBlock::class, ProductGridBlock::class, BrandGridBlock::class, RichTextBlock::class], StorefrontBlock::TAG);
        $this->app->make(Extensions::class)->tag([CategoryLinkType::class, CollectionLinkType::class, BrandLinkType::class, ProductLinkType::class, RouteLinkType::class, UrlLinkType::class], LinkType::TAG);
        $permissions->register('storefront.manage', 'storefront::texts.storefront_service_provider.edit_storefront_design_home_page');
        $navigation->group('content', 'storefront::texts.storefront_service_provider.content_design', 590);
        $navigation->add('storefront', 'storefront::texts.storefront_service_provider.storefront_design', 'admin.storefront.home', 'storefront.manage', 600, group: 'content');

        $router->aliasMiddleware('vani.theme', UseActiveTheme::class);
        $router->aliasMiddleware('vani.page-cache', PublicPageCache::class);
        // Thêm vào giỏ từ trang đã cache CDN (không có token CSRF trong HTML, storefront §5), mọi ngôn ngữ. An toàn: cookie
        // phiên SameSite=Lax nên form chéo trang không mang phiên theo — không đụng được giỏ của nạn nhân.
        // Beacon lượt xem PDP (§3.5-2): navigator.sendBeacon không gửi được header CSRF.
        PreventRequestForgery::except(['api/storefront/v1/products/views']);
        PreventRequestForgery::except(array_map(
            fn (string $locale): string => trim(LocalizedPaths::prefix($locale).'/'.LocalizedPaths::path('cart', $locale), '/'),
            StoreLocale::supported(),
        ));
        $router->aliasMiddleware('vani.maintenance', StorefrontMaintenance::class);
        $router->aliasMiddleware('vani.prices-visible', RequireVisiblePrices::class);
        $router->aliasMiddleware('vani.checkout-sign-in', RequireCheckoutSignIn::class);
        // Dữ liệu dựng sẵn cho theme — template chỉ in ra (layout, danh sách, ảnh, khung ảnh sản phẩm, thông báo, menu tài khoản).
        View::composer('theme::layouts.app', LayoutComposer::class);
        View::composer('theme::partials.listing', ListingComposer::class);
        View::composer('theme::partials.image', ImageComposer::class);
        View::composer(['theme::partials.product-card', 'theme::partials.mini-cart'], ProductFrameComposer::class);
        View::composer('theme::partials.notification', NotificationComposer::class);
        View::composer('theme::pages.search', fn (ViewInstance $view) => $view->with('popularSearches', $this->app->make(StoreProfile::class)->popularSearches()));
        // Breadcrumb dựng sẵn (Trang chủ, link có kiểu, JSON-LD) — partial chỉ hiển thị `$breadcrumb`.
        View::composer('theme::partials.breadcrumb', fn (ViewInstance $view) => $view->with('breadcrumb', $this->app->make(Breadcrumbs::class)->trail((array) ($view->getData()['crumbs'] ?? []))));
        View::composer('theme::partials.account-menu', AccountMenuComposer::class);
        Blade::componentNamespace('Modules\\Storefront\\View\\Components', 'vani');

        $settings = $this->app->make(Settings::class);
        $settings->define(new SettingDefinition(
            'core', 'theme', 'storefront::texts.storefront_service_provider.storefront_theme', 'select', (string) config('vanishop.storefront.theme', Themes::BASE),
            options: array_map(fn (Theme $theme): string => $theme->label, $this->app->make(Themes::class)->all()),
            help: 'storefront::texts.storefront_service_provider.store_s_active_theme_custom',
        ));
        $settings->define(new SettingDefinition(
            'core', 'theme.tokens', 'storefront::texts.storefront_service_provider.design_tokens_json', 'text',
            help: 'storefront::texts.storefront_service_provider.override_theme_s_tokens_color',
        ));
        $settings->define(new SettingDefinition(
            'core', 'storefront.maintenance', 'storefront::texts.storefront_service_provider.store_maintenance', 'bool', false,
            help: 'storefront::texts.storefront_service_provider.storefront_storefront_api_return_503',
        ));
        $settings->define(new SettingDefinition(
            'core', 'storefront.maintenance_message', 'storefront::texts.storefront_service_provider.maintenance_message', 'text',
            help: 'storefront::texts.storefront_service_provider.maintenance_page_text_leave_empty',
            rules: ['max:1000'],
        ));
        $settings->define(new SettingDefinition(
            'core', PriceVisibility::SETTING, 'storefront::texts.storefront_service_provider.show_prices', 'select', PriceVisibility::PUBLIC,
            options: [PriceVisibility::PUBLIC => 'storefront::texts.storefront_service_provider.everyone', PriceVisibility::MEMBERS => 'storefront::texts.storefront_service_provider.signed_customers_only'],
            help: 'storefront::texts.storefront_service_provider.signed_customers_only_guests_see',
        ));

        $settings->define(new SettingDefinition(
            'core', 'storefront.per_page', 'storefront::texts.storefront_service_provider.products_per_page', 'int', 24,
            help: 'storefront::texts.storefront_service_provider.category_brand_search_pages_storefront',
            rules: ['min:12', 'max:60'],
        ));
        $settings->define(new SettingDefinition(
            'core', StoreShare::DESCRIPTION_SETTING, 'storefront::texts.storefront_service_provider.store_description_google_link_sharing', 'text',
            help: 'storefront::texts.storefront_service_provider.shown_under_store_name_google',
            rules: ['max:300'],
        ));
        $settings->define(new SettingDefinition(
            'core', StoreShare::IMAGE_SETTING, 'storefront::texts.storefront_service_provider.link_sharing_image', 'media',
            help: 'storefront::texts.storefront_service_provider.facebook_zalo_image_shown_when',
            // Ảnh không còn trong thư viện → bỏ qua (StoreShare trả null), không cần kiểm bảng của Catalog ở đây.
            rules: ['min:1'],
        ));
        $settings->define(new SettingDefinition(
            'core', PopularSearches::SETTING, 'storefront::texts.storefront_service_provider.featured_search_keywords', 'text',
            help: 'storefront::texts.storefront_service_provider.one_keyword_per_line_most',
            rules: ['max:1000'],
        ));

        // Tạo giỏ không cần đăng nhập → giới hạn riêng, chặt hơn, chống spam bảng carts.
        // Tra cứu đơn bằng số đơn + SĐT: chặn dò số điện thoại.
        RateLimiter::for('vani-order-track', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.order_track', 10)));
        RateLimiter::for('vani-checkout', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.checkout', 20)));
        RateLimiter::for('vani-voucher', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.voucher', 10), 'voucher:'));
        RateLimiter::for('vani-cart-create', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.cart_create', 30)));

        $this->loadStorefrontApiRoutes($this->modulePath('Http/routes/storefront-api.php'));
        $this->loadWebRoutes($this->modulePath('Http/routes/storefront-web.php'));
        $this->loadAdminRoutes($this->modulePath('Http/routes/admin-home.php'));
        $this->loadAdminSectionRoutes('storefront', $this->modulePath('Http/routes/admin-workspace.php'));
        $this->bootModuleResources();
    }
}
