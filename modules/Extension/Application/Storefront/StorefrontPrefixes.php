<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Storefront;

use InvalidArgumentException;
use Modules\Shared\Support\AdminPath;
use Modules\Shared\Support\StoreLocale;

/**
 * Đoạn đầu URL storefront mà plugin xin dùng cho trang native (`storefrontPages(..., prefix: 'tin-tuc')`).
 * Không trùng route của Core (danh sách giữ chỗ dưới đây — arch test đối chiếu với route Core) hay plugin khác.
 */
final class StorefrontPrefixes
{
    /** Đoạn đầu của route Core (mọi ngôn ngữ, ADR-038) + đường dẫn hệ thống. Mã ngôn ngữ của cửa hàng cũng bị giữ chỗ. */
    public const RESERVED = [
        'danh-muc', 'thuong-hieu', 'tim-kiem', 'san-pham', 'gio-hang', 'thanh-toan', 'don-hang', 'tra-cuu-don', 'tai-khoan',
        'category', 'brands', 'search', 'products', 'cart', 'checkout', 'orders', 'track-order', 'account',
        'p', 'api', 'storage', 'build', 'vendor', 'up', 'health', 'robots.txt', 'sitemap.xml', 'favicon.ico', 'livewire', 'sanctum', 'cache', '_vani',
    ];

    /** @var array<string, string> "locale:prefix" => plugin id */
    private array $claimed = [];

    public function claim(string $prefix, string $pluginId, ?string $locale = null): void
    {
        $locale ??= StoreLocale::default();
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $prefix) !== 1) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] không hợp lệ (chữ thường, số, gạch nối).");
        }
        if (in_array($prefix, self::RESERVED, true) || in_array($prefix, StoreLocale::supported(), true) || $prefix === explode('/', AdminPath::prefix())[0]) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] đã được Core dùng.");
        }
        $key = "{$locale}:{$prefix}";
        if (isset($this->claimed[$key]) && $this->claimed[$key] !== $pluginId) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] ({$locale}) đã thuộc plugin [{$this->claimed[$key]}].");
        }

        $this->claimed[$key] = $pluginId;
    }

    /**
     * @return array<string, string> "locale:prefix" => plugin id
     */
    public function all(): array
    {
        return $this->claimed;
    }
}
