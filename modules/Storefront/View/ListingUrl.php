<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

use Illuminate\Http\Request;

/**
 * URL cho bộ lọc/phân trang của trang danh sách: link thường (không cần JS — ADR-025), giữ các tham số khác,
 * giá trị nhiều lựa chọn nối bằng dấu phẩy như Storefront API (`?brand=a,b&color=black`).
 */
final class ListingUrl
{
    public static function toggle(Request $request, string $key, string $value): string
    {
        $query = $request->query();
        $values = self::values($request, $key);
        $values = in_array($value, $values, true) ? array_values(array_diff($values, [$value])) : [...$values, $value];

        $query[$key] = implode(',', $values);
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
        return array_values(array_filter(array_map('trim', explode(',', (string) $request->query($key, ''))), fn (string $part): bool => $part !== ''));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private static function url(Request $request, array $query): string
    {
        $query = array_filter($query, fn (mixed $value): bool => $value !== '' && $value !== null && $value !== []);

        return $request->url().($query === [] ? '' : '?'.http_build_query($query));
    }
}
