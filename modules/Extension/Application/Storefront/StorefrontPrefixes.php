<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Storefront;

use InvalidArgumentException;
use Modules\Shared\Support\AdminPath;

/**
 * Đoạn đầu URL storefront mà plugin xin dùng cho trang native (`storefrontPages(..., prefix: 'tin-tuc')`).
 * Không trùng route của Core (danh sách giữ chỗ dưới đây — arch test đối chiếu với route Core) hay plugin khác.
 */
final class StorefrontPrefixes
{
    /** Đoạn đầu của route Core + đường dẫn hệ thống. */
    public const RESERVED = [
        'danh-muc', 'thuong-hieu', 'tim-kiem', 'san-pham', 'gio-hang', 'thanh-toan', 'don-hang', 'tra-cuu-don', 'tai-khoan',
        'p', 'api', 'storage', 'build', 'vendor', 'up', 'robots.txt', 'sitemap.xml', 'favicon.ico', 'livewire', 'sanctum',
    ];

    /** @var array<string, string> prefix => plugin id */
    private array $claimed = [];

    public function claim(string $prefix, string $pluginId): void
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $prefix) !== 1) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] không hợp lệ (chữ thường, số, gạch nối).");
        }
        if (in_array($prefix, self::RESERVED, true) || $prefix === explode('/', AdminPath::prefix())[0]) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] đã được Core dùng.");
        }
        if (isset($this->claimed[$prefix]) && $this->claimed[$prefix] !== $pluginId) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] đã thuộc plugin [{$this->claimed[$prefix]}].");
        }

        $this->claimed[$prefix] = $pluginId;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->claimed;
    }
}
