<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

use Illuminate\Http\Request;

/**
 * URL cho bộ lọc/phân trang của trang danh sách: link thường (không cần JS — ADR-025), giữ các tham số khác,
 * giá trị nhiều lựa chọn nối bằng dấu phẩy như Storefront API (`?brand=a,b&color=black`). Khoá lồng viết bằng dấu chấm:
 * `attr.material` = `?attr[material]=silk,linen`.
 */
final class ListingUrl
{
    public static function toggle(Request $request, string $key, string $value): string
    {
        $query = $request->query();
        $values = self::values($request, $key);
        $values = in_array($value, $values, true) ? array_values(array_diff($values, [$value])) : [...$values, $value];

        data_set($query, $key, implode(',', $values));
        unset($query['page']);

        return self::url($request, $query);
    }

    public static function page(Request $request, int $page): string
    {
        return self::url($request, [...$request->query(), 'page' => $page]);
    }

    public static function active(Request $request, string $key, string $value): bool
    {
        return in_array($value, self::values($request, $key), true);
    }

    /**
     * @return list<string>
     */
    private static function values(Request $request, string $key): array
    {
        $raw = data_get($request->query(), $key, '');

        return array_values(array_filter(array_map('trim', explode(',', is_string($raw) ? $raw : '')), fn (string $part): bool => $part !== ''));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private static function url(Request $request, array $query): string
    {
        $clean = function (array $values) use (&$clean): array {
            $values = array_map(fn (mixed $value): mixed => is_array($value) ? $clean($value) : $value, $values);

            return array_filter($values, fn (mixed $value): bool => $value !== '' && $value !== null && $value !== []);
        };
        $query = $clean($query);

        return $request->url().($query === [] ? '' : '?'.http_build_query($query));
    }
}
