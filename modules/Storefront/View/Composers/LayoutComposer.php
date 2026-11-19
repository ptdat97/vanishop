<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Composers;

use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\CustomerSessionState;
use Modules\Storefront\Application\Menus\MenuPresenter;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Application\Seo\StoreShare;
use Modules\Storefront\Application\StoreProfile;
use Modules\Storefront\Http\Controllers\Web\CartController;
use Modules\Storefront\Http\Middleware\PublicPageCache;
use Modules\Storefront\View\StructuredData;

/**
 * Dữ liệu dựng sẵn cho `theme::layouts.app` (và partial nó include): `$shell` gom mọi thứ layout cần tính — chế độ cache
 * trang, menu theo vị trí (di động rơi về đầu trang), thanh thông báo, liên hệ/mạng xã hội/phương thức thanh toán ở chân
 * trang, link tài khoản theo khách, ô tìm kiếm, thay đổi giỏ của lần gửi trước, URL cho `window.vani`, giá trị chia sẻ
 * mặc định (`share`: tên, mô tả, ảnh của cửa hàng cho thẻ Open Graph; JSON-LD cửa hàng ở trang chủ). Layout chỉ in ra.
 * Giữ `$menus`, `$seo`, `$themeSettings`, `$popularSearches`, `$customerState` như trước cho theme con.
 */
final class LayoutComposer
{
    private const SOCIALS = ['Facebook' => 'facebook_url', 'Instagram' => 'instagram_url', 'TikTok' => 'tiktok_url', 'YouTube' => 'youtube_url', 'Zalo' => 'zalo_url'];

    private const FOOTER_MENUS = ['footer_1', 'footer_2', 'footer_3', 'footer_4'];

    public function __construct(
        private readonly Request $request,
        private readonly MenuPresenter $menus,
        private readonly PageAlternates $alternates,
        private readonly StoreProfile $store,
        private readonly CustomerSessionState $sessionState,
        private readonly SessionManager $session,
        private readonly StoreShare $share,
    ) {}

    public function compose(View $view): void
    {
        $locale = App::getLocale();
        $pageCache = $this->request->attributes->has(PublicPageCache::ATTRIBUTE);
        $themeSettings = $this->store->themeSettings($locale);

        $view->with([
            'menus' => $this->menus,
            'seo' => $this->alternates->tags(),
            // Đầu/chân trang theo schema theme (§3.16-3) — cùng nguồn với Storefront API `GET /store` (StoreProfile, ADR-044).
            'themeSettings' => $themeSettings,
            'popularSearches' => $this->store->popularSearches(),
            // Không CDN (mặc định): phần riêng của khách render sẵn; có CDN thì JS lấy qua /_vani/phien (storefront §5).
            'customerState' => $pageCache ? null : $this->sessionState->for($this->request, $locale),
            'shell' => $this->shell($locale, $pageCache, $themeSettings),
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $themeSettings
     * @return array<string, mixed>
     */
    private function shell(string $locale, bool $pageCache, array $themeSettings): array
    {
        $header = $themeSettings['header'] ?? [];
        $footer = $themeSettings['footer'] ?? [];
        $headerMenu = $this->menus->items('header', $locale);
        $signedIn = $this->request->attributes->has('customer');
        $socials = [];
        foreach (self::SOCIALS as $network => $key) {
            if (($footer[$key] ?? null) !== null && $footer[$key] !== '') {
                $socials[] = ['network' => $network, 'url' => (string) $footer[$key]];
            }
        }
        $phone = ($footer['contact_phone'] ?? null) ?: null;
        $email = ($footer['contact_email'] ?? null) ?: null;
        $address = ($footer['contact_address'] ?? null) ?: null;
        $flash = $pageCache ? null : $this->session->get(CartController::CHANGE_FLASH);

        return [
            'page_cache' => $pageCache,
            'session_url' => $pageCache ? LocalizedUrls::route('storefront.session', ['locale' => $locale]) : null,
            'announcement' => ($header['announcement'] ?? null) ? ['text' => (string) $header['announcement'], 'url' => ($header['announcement_url'] ?? null) ?: null] : null,
            'menus' => [
                'header' => $headerMenu,
                'mobile' => $this->menus->items('mobile', $locale) ?? $headerMenu,
                // Vị trí chưa xuất bản bị bỏ; không vị trí nào → theme hiện link mặc định.
                'footer' => array_filter(array_combine(self::FOOTER_MENUS, array_map(fn (string $location): ?array => $this->menus->items($location, $locale), self::FOOTER_MENUS)), fn (?array $items): bool => $items !== null),
            ],
            'search_query' => $this->request->routeIs('storefront.search', 'storefront.*.search') ? (string) $this->request->query('q', '') : '',
            'account' => [
                'url' => LocalizedUrls::route($signedIn ? 'storefront.account' : 'storefront.account.login'),
                'label' => $signedIn ? __('storefront::labels.account') : __('storefront::labels.sign_in'),
            ],
            'mobile_topics_url' => (($themeSettings['mobile_nav']['topics_url'] ?? null) ?: null) ?? LocalizedUrls::route('storefront.brands'),
            'contact' => $phone || $email || $address || $socials !== [] ? [
                'phone' => $phone, 'phone_href' => $phone === null ? null : 'tel:'.preg_replace('/[^\d+]/', '', (string) $phone),
                'email' => $email, 'address' => $address, 'socials' => $socials,
            ] : null,
            'copyright' => ($footer['copyright'] ?? null) ?: '© '.date('Y').' '.config('app.name'),
            'share' => $this->shareDefaults($locale, $phone, $socials),
            'payment_methods' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($footer['payment_methods'] ?? '')) ?: []))),
            // Thay đổi giỏ của lần gửi trước → sự kiện `vani:cart-updated` (app.js). Trang có cache lấy qua /_vani/phien.
            'cart_change' => is_array($flash) ? $flash : null,
            // URL cho window.vani (app.js) theo ngôn ngữ trang; `987654321` = chỗ app.js thay bằng id dòng giỏ.
            'urls' => [
                'cart' => LocalizedUrls::route('storefront.cart'),
                'cart_add' => LocalizedUrls::route('storefront.cart.add'),
                'cart_line' => LocalizedUrls::route('storefront.cart.update', ['line' => 987654321]),
                'cart_remove' => LocalizedUrls::route('storefront.cart.remove', ['line' => 987654321]),
                'cart_select' => LocalizedUrls::route('storefront.cart.select'),
                'cart_voucher' => LocalizedUrls::route('storefront.cart.voucher'),
                'checkout' => LocalizedUrls::route('storefront.checkout'),
                'login' => LocalizedUrls::route('storefront.account.login'),
                'login_authenticate' => LocalizedUrls::route('storefront.account.authenticate'),
                'logout' => LocalizedUrls::route('storefront.account.logout'),
                'search' => LocalizedUrls::route('storefront.search'),
                'search_suggestions' => route('api.storefront.v1.search.suggestions'),
                'session' => LocalizedUrls::route('storefront.session', ['locale' => $locale]),
            ],
        ];
    }

    /**
     * Thẻ chia sẻ mặc định (Open Graph): trang tự đặt tiêu đề/mô tả/ảnh bằng section `title`, `description`, `og_image`,
     * `og_type`; thiếu thì layout dùng giá trị ở đây.
     *
     * @param  list<array{network: string, url: string}>  $socials
     * @return array{site_name: string, description: string|null, image: string|null, locale: string, json_ld: string|null}
     */
    private function shareDefaults(string $locale, ?string $phone, array $socials): array
    {
        $defaults = $this->share->defaults();
        $home = $this->request->routeIs('storefront.home', 'storefront.*.home');

        return [
            ...$defaults,
            'locale' => (string) (config('vanishop.locale.og_locales')[$locale] ?? $locale),
            'json_ld' => $home ? StructuredData::store(
                $defaults['site_name'],
                LocalizedUrls::route('storefront.home'),
                LocalizedUrls::route('storefront.search'),
                $defaults['image'],
                $phone,
                array_column($socials, 'url'),
            ) : null,
        ];
    }
}
