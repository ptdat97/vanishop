<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Application\Theme\ThemeSettings;
use Modules\Tenancy\Contracts\Settings;

/**
 * "Vỏ" của cửa hàng cho mọi kênh (ADR-044, api-first-refactor đợt A2): thông tin cần để dựng đầu/chân trang và quyết định
 * cách hiển thị — Storefront API `GET /store` và layout Blade đọc cùng một chỗ. Chỉ dữ liệu công khai.
 */
final class StoreProfile
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Themes $themes,
        private readonly ThemeSettings $themeSettings,
        private readonly PriceVisibility $prices,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * @return array{
     *     name: string,
     *     locale: array{current: string, default: string, supported: list<array{code: string, label: string}>},
     *     currency: array<string, mixed>,
     *     prices: array{visibility: string, hidden: bool},
     *     guest_checkout: bool,
     *     popular_searches: list<string>,
     *     menu_locations: list<array{code: string, label: string, depth: int}>,
     *     theme: array{settings: array<string, array<string, mixed>>}
     * }
     */
    public function present(string $locale): array
    {
        $currency = (string) config('vanishop.currency');

        return [
            'name' => (string) config('app.name'),
            'locale' => ['current' => $locale, 'default' => StoreLocale::default(), 'supported' => StoreLocale::options()],
            // Quy tắc định dạng tiền của cửa hàng (ký hiệu, dấu phân cách, số chữ số lẻ) — client định dạng giống server.
            'currency' => $this->money->rules($currency, $locale),
            'prices' => [
                'visibility' => $this->prices->membersOnly() ? PriceVisibility::MEMBERS : PriceVisibility::PUBLIC,
                // Giá có bị ẩn với người đang xem (khách chưa đăng nhập khi cửa hàng chỉ hiện giá cho thành viên).
                'hidden' => $this->prices->hidden(),
            ],
            'guest_checkout' => (bool) $this->settings->get('core', 'storefront.guest_checkout', true),
            'popular_searches' => $this->popularSearches(),
            'menu_locations' => array_map(fn (string $code, array $location): array => ['code' => $code, 'label' => (string) $location['label'], 'depth' => (int) $location['depth']], array_keys($this->themes->menuLocations()), $this->themes->menuLocations()),
            // Cài đặt đầu/chân trang của theme (Admin → Giao diện → Đầu & chân trang), đã chọn theo ngôn ngữ.
            'theme' => ['settings' => $this->themeSettings->values($locale)],
        ];
    }

    /**
     * @return list<string>
     */
    public function popularSearches(): array
    {
        return PopularSearches::from($this->settings);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function themeSettings(string $locale): array
    {
        return $this->themeSettings->values($locale);
    }
}
