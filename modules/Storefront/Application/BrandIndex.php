<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Support\Str;
use Normalizer;

/**
 * Thương hiệu nhóm theo chữ cái đầu (reference-parity §3.5-3): bỏ dấu thanh/mũ khi nhóm (Ấ → A) bằng tách tổ hợp
 * Unicode; chữ không tách được giữ nhóm riêng (Đ ≠ D, xếp ngay sau D). Ký tự không phải chữ → nhóm "#", cuối danh sách.
 */
final class BrandIndex
{
    public const OTHER = '#';

    /**
     * @param  list<array<string, mixed>>  $brands  mỗi phần tử có `name`
     * @return array<string, list<array<string, mixed>>> chữ cái => thương hiệu (theo tên)
     */
    public static function group(array $brands): array
    {
        $groups = [];
        foreach ($brands as $brand) {
            $groups[self::letter((string) $brand['name'])][] = $brand;
        }
        foreach ($groups as &$items) {
            usort($items, fn (array $a, array $b): int => strcasecmp(Str::ascii((string) $a['name']), Str::ascii((string) $b['name'])));
        }
        unset($items);

        uksort($groups, function (string $a, string $b): int {
            if ($a === self::OTHER || $b === self::OTHER) {
                return ($a === self::OTHER) <=> ($b === self::OTHER);
            }

            return [Str::ascii($a), $a] <=> [Str::ascii($b), $b];
        });

        return $groups;
    }

    public static function letter(string $name): string
    {
        $first = mb_strtoupper(mb_substr(trim($name), 0, 1));
        $decomposed = Normalizer::normalize($first, Normalizer::FORM_D);
        $base = mb_substr($decomposed === false ? $first : $decomposed, 0, 1);

        return preg_match('/^\p{L}$/u', $base) === 1 ? $base : self::OTHER;
    }
}
