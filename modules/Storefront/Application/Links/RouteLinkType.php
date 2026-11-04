<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Storefront\Contracts\LinkType;

/**
 * Link tới trang storefront công khai theo tên route — chỉ các route trong danh sách cho phép (không lộ route nội bộ,
 * route cần tham số). Plugin có trang riêng thì đăng ký `LinkType` của plugin.
 */
final class RouteLinkType implements LinkType
{
    /** @var array<string, string> tên route => nhãn mặc định */
    private const ROUTES = [
        'storefront.home' => 'Trang chủ',
        'storefront.brands' => 'Thương hiệu',
        'storefront.search' => 'Tất cả sản phẩm',
        'storefront.cart' => 'Giỏ hàng',
        'storefront.track' => 'Tra cứu đơn hàng',
        'storefront.account' => 'Tài khoản',
        'storefront.account.orders' => 'Đơn hàng của tôi',
    ];

    public function type(): string
    {
        return 'route';
    }

    public function label(): string
    {
        return 'Trang của cửa hàng';
    }

    public function searchable(): bool
    {
        return false;
    }

    public function options(): array
    {
        return array_map(fn (string $route, string $label): array => ['value' => $route, 'label' => $label], array_keys(self::ROUTES), self::ROUTES);
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
                $resolved[$value] = ['url' => route($value), 'label' => self::ROUTES[$value]];
            }
        }

        return $resolved;
    }
}
