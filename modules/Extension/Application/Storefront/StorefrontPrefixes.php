<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Storefront;

use InvalidArgumentException;
use Modules\Shared\Support\AdminPath;
use Modules\Shared\Support\StoreLocale;

/**
 * Đoạn đầu URL storefront mà plugin xin dùng cho trang native (`storefrontPages(..., prefix: 'tin-tuc')`).
 * Không trùng route của Core hay plugin khác. Đoạn đầu của route Core lấy từ `vanishop.locale.paths` của cửa hàng
 * (mọi ngôn ngữ, ADR-038) — vòng 0 không ghi tên trang thương mại (R29); arch test đối chiếu với route Core.
 */
final class StorefrontPrefixes
{
    /** Đường dẫn hệ thống. Mã ngôn ngữ của cửa hàng và tiền tố Admin cũng bị giữ chỗ. */
    public const RESERVED = [
        'p', 'api', 'storage', 'build', 'vendor', 'up', 'health', 'robots.txt', 'sitemap.xml', 'sitemap', 'favicon.ico', 'livewire', 'sanctum', 'cache', '_vani',
    ];

    /** @var array<string, string> "locale:prefix" => plugin id */
    private array $claimed = [];

    /**
     * Mọi đoạn đầu plugin không được dùng: đường dẫn hệ thống, mã ngôn ngữ, tiền tố Admin, đoạn đầu route Core.
     *
     * @return list<string>
     */
    public function reserved(): array
    {
        $reserved = [...self::RESERVED, ...StoreLocale::supported(), explode('/', AdminPath::prefix())[0]];
        foreach ((array) config('vanishop.locale.paths') as $paths) {
            foreach ((array) $paths as $path) {
                $reserved[] = explode('/', trim((string) $path, '/'))[0];
            }
        }

        return array_values(array_unique(array_filter($reserved, fn (string $segment): bool => $segment !== '')));
    }

    public function claim(string $prefix, string $pluginId, ?string $locale = null): void
    {
        $locale ??= StoreLocale::default();
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $prefix) !== 1) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] không hợp lệ (chữ thường, số, gạch nối).");
        }
        if (in_array($prefix, $this->reserved(), true)) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] đã được Core dùng.");
        }
        $key = "{$locale}:{$prefix}";
        if (isset($this->claimed[$key]) && $this->claimed[$key] !== $pluginId) {
            throw new InvalidArgumentException("Prefix storefront [{$prefix}] ({$locale}) đã thuộc plugin [{$this->claimed[$key]}].");
        }

        $this->claimed[$key] = $pluginId;
    }

    /**
     * Đoạn đường dẫn trang plugin dưới trang tài khoản (0.3.76, `accountRoutes`): không trùng trang tài khoản của Core
     * (đoạn thứ hai của `account.*` trong `vanishop.locale.paths`) hay plugin khác.
     */
    public function claimAccount(string $segment, string $pluginId, string $locale): void
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $segment) !== 1) {
            throw new InvalidArgumentException("Đường dẫn tài khoản [{$segment}] không hợp lệ (chữ thường, số, gạch nối).");
        }
        $paths = (array) config('vanishop.locale.paths.'.$locale, []) ?: (array) config('vanishop.locale.paths.'.StoreLocale::default(), []);
        foreach ($paths as $name => $path) {
            if (str_starts_with((string) $name, 'account.') && (explode('/', (string) $path)[1] ?? null) === $segment) {
                throw new InvalidArgumentException("Đường dẫn tài khoản [{$segment}] ({$locale}) đã được Core dùng.");
            }
        }
        $key = "account:{$locale}:{$segment}";
        if (isset($this->claimed[$key]) && $this->claimed[$key] !== $pluginId) {
            throw new InvalidArgumentException("Đường dẫn tài khoản [{$segment}] ({$locale}) đã thuộc plugin [{$this->claimed[$key]}].");
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
