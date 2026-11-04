<?php

declare(strict_types=1);

namespace Modules\Storefront\Http;

use InvalidArgumentException;
use Modules\Shared\Support\StoreLocale;

/**
 * Đường dẫn storefront theo ngôn ngữ — khai báo một chỗ ở `config/vanishop.php` (`locale.paths`, ADR-038). Khoá = tên
 * route bỏ tiền tố `storefront.`. Ngôn ngữ mặc định không có tiền tố; ngôn ngữ khác dưới `/<mã ngôn ngữ>`.
 */
final class LocalizedPaths
{
    public static function path(string $key, string $locale): string
    {
        $paths = (array) config('vanishop.locale.paths', []);
        $default = (array) ($paths[StoreLocale::default()] ?? []);
        $path = $paths[$locale][$key] ?? $default[$key] ?? throw new InvalidArgumentException("Không có đường dẫn storefront [{$key}].");

        return (string) $path;
    }

    /** Tiền tố đường dẫn của ngôn ngữ: rỗng cho ngôn ngữ mặc định. */
    public static function prefix(string $locale): string
    {
        return $locale === StoreLocale::default() ? '' : $locale;
    }

    /** Tiền tố tên route: `storefront.` hoặc `storefront.<locale>.`. */
    public static function namePrefix(string $locale): string
    {
        return $locale === StoreLocale::default() ? 'storefront.' : "storefront.{$locale}.";
    }
}
