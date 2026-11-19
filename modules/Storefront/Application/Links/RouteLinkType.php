<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Contracts\LinkType;

/**
 * Link tới trang storefront công khai theo tên route — chỉ các route trong danh sách cho phép (không lộ route nội bộ,
 * route cần tham số). Plugin có trang riêng thì đăng ký `LinkType` của plugin.
 */
final class RouteLinkType implements LinkType
{
    /** @var array<string, string> tên route => nhãn mặc định */
    private const ROUTES = [
        'storefront.home' => 'storefront::texts.route_link_type.home_page',
        'storefront.categories' => 'storefront::texts.route_link_type.category',
        'storefront.brands' => 'storefront::texts.route_link_type.brand',
        'storefront.search' => 'storefront::texts.route_link_type.all_products',
        'storefront.cart' => 'storefront::texts.route_link_type.cart',
        'storefront.track' => 'storefront::texts.route_link_type.track_order',
        'storefront.account' => 'storefront::texts.route_link_type.account',
        'storefront.account.orders' => 'storefront::texts.route_link_type.my_orders',
    ];

    public function type(): string
    {
        return 'route';
    }

    public function label(): string
    {
        return __('storefront::texts.route_link_type.store_page');
    }

    public function searchable(): bool
    {
        return false;
    }

    public function options(): array
    {
        return array_map(fn (string $route, string $label): array => ['value' => $route, 'label' => __($label)], array_keys(self::ROUTES), self::ROUTES);
    }

    public function search(string $query, string $locale, int $limit = 20): array
    {
        return [];
    }

    public function accepts(string $value): bool
    {
        return isset(self::ROUTES[$value]);
    }

    public function resolve(array $values, string $locale): array
    {
        $resolved = [];
        foreach ($values as $value) {
            if ($this->accepts($value) && app('router')->has($value)) {
                $resolved[$value] = ['url' => LocalizedUrls::route($value, [], $locale), 'label' => __(self::ROUTES[$value])];
            }
        }

        return $resolved;
    }
}
