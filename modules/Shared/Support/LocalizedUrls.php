<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/**
 * URL storefront theo ngôn ngữ (ADR-038): ngôn ngữ mặc định giữ tên route gốc (`storefront.product`), ngôn ngữ khác
 * đăng ký dưới tên `storefront.<locale>.…` (vd. `storefront.en.product` → `/en/products/{slug}`). Route chưa có bản
 * theo ngôn ngữ (vd. trang plugin chỉ khai báo tiền tố tiếng Việt) → URL ngôn ngữ mặc định.
 */
final class LocalizedUrls
{
    private const PREFIX = 'storefront.';

    /**
     * @param  mixed  $parameters  như tham số của `route()`
     */
    public static function route(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return route(self::name($name, $locale ?? App::getLocale()), $parameters, $absolute);
    }

    /** Tên route của `$name` ở `$locale` nếu có, ngược lại tên gốc. */
    public static function name(string $name, string $locale): string
    {
        $base = self::baseName($name);
        if ($locale === StoreLocale::default() || ! str_starts_with($base, self::PREFIX)) {
            return $base;
        }
        $localized = self::PREFIX.$locale.'.'.substr($base, strlen(self::PREFIX));

        return Route::has($localized) ? $localized : $base;
    }

    /** Route có bản riêng cho `$locale` (ngôn ngữ mặc định: route gốc tồn tại). */
    public static function has(string $name, string $locale): bool
    {
        $base = self::baseName($name);

        return Route::has(self::name($base, $locale)) && ($locale === StoreLocale::default() || self::name($base, $locale) !== $base);
    }

    /** Tên gốc (ngôn ngữ mặc định) của một route đã theo ngôn ngữ: `storefront.en.product` → `storefront.product`. */
    public static function baseName(string $name): string
    {
        foreach (StoreLocale::supported() as $locale) {
            $prefix = self::PREFIX.$locale.'.';
            if ($locale !== StoreLocale::default() && str_starts_with($name, $prefix)) {
                return self::PREFIX.substr($name, strlen($prefix));
            }
        }

        return $name;
    }
}
