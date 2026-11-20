<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Style;

/**
 * Mã sản phẩm và slug tự sinh — form thêm sản phẩm, nhân bản, nhập file dùng chung.
 */
final class ProductIdentifiers
{
    /**
     * Slug không dấu theo tên (tên rỗng → theo mã), trùng thì thêm `-2`, `-3`…; `$ignoreStyleId` = sản phẩm đang sửa.
     */
    public static function uniqueSlug(string $name, string $styleCode, ?int $ignoreStyleId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: Str::slug($styleCode), 150, '');
        $slug = $base;
        for ($suffix = 2; Style::query()->withoutGlobalScopes()->where('slug', $slug)->when($ignoreStyleId, fn ($query) => $query->whereKeyNot($ignoreStyleId))->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * Mã gợi ý cho sản phẩm mới: tăng số cuối của mã vừa tạo gần nhất, giữ độ dài (AO012 → AO013); mã gần nhất không có
     * số cuối hoặc chưa có sản phẩm → SP0001. Bỏ qua mã đã có.
     */
    public static function suggestStyleCode(): string
    {
        $latest = (string) Style::query()->withoutGlobalScopes()->latest('id')->value('style_code');
        [$prefix, $digits] = preg_match('/^(.*?)(\d+)$/', $latest, $match) === 1 ? [$match[1], $match[2]] : ['SP', '0000'];
        $number = (int) $digits;
        do {
            $number++;
            $code = $prefix.str_pad((string) $number, strlen($digits), '0', STR_PAD_LEFT);
        } while (Style::query()->withoutGlobalScopes()->where('style_code', $code)->exists());

        return $code;
    }
}
