<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Tenancy\Contracts\Settings;

/**
 * Mã sản phẩm và slug tự sinh — form thêm sản phẩm, nhân bản, nhập file dùng chung.
 */
final class ProductIdentifiers
{
    /**
     * Slug không dấu theo tên (tên rỗng → theo mã), trùng thì thêm `-2`, `-3`…; `$ignoreProductId` = sản phẩm đang sửa.
     */
    public static function uniqueSlug(string $name, string $productCode, ?int $ignoreProductId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: Str::slug($productCode), 150, '');
        $slug = $base;
        for ($suffix = 2; Product::query()->withoutGlobalScopes()->where('slug', $slug)->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /** Tiền tố mã sản phẩm (Admin → Cài đặt → Cửa hàng); trống = tăng số cuối của mã gần nhất. */
    public const PREFIX_SETTING = 'catalog.product_code_prefix';

    /** Số chữ số phần số thứ tự của mã gợi ý. */
    public const DIGITS_SETTING = 'catalog.product_code_digits';

    /**
     * Mã gợi ý cho sản phẩm mới. Có tiền tố (vd. `SP`, 4 chữ số): tiền tố + số lớn nhất của các mã dạng `SP<số>` cộng 1
     * (SP0001, SP0002…). Không có tiền tố: tăng số cuối của mã tạo gần nhất, giữ độ dài (AO012 → AO013); mã gần nhất không
     * có số cuối hoặc chưa có sản phẩm → `SP` + số chữ số. Luôn bỏ qua mã đã có.
     */
    public static function suggestProductCode(Settings $settings): string
    {
        $prefix = strtoupper(trim((string) $settings->get('core', self::PREFIX_SETTING)));
        $width = max(1, min(10, (int) $settings->get('core', self::DIGITS_SETTING)));

        if ($prefix !== '') {
            // LIKE lọc thô (`_` trong tiền tố khớp rộng hơn), regex lọc chính xác.
            $number = Product::query()->withoutGlobalScopes()->where('product_code', 'like', $prefix.'%')->pluck('product_code')
                ->map(fn (string $code): int => preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $code, $match) === 1 ? (int) $match[1] : 0)
                ->max() ?? 0;
        } else {
            $latest = (string) Product::query()->withoutGlobalScopes()->latest('id')->value('product_code');
            [$prefix, $digits] = preg_match('/^(.*?)(\d+)$/', $latest, $match) === 1 ? [$match[1], $match[2]] : ['SP', str_repeat('0', $width)];
            $number = (int) $digits;
            $width = strlen($digits);
        }

        do {
            $number++;
            $code = $prefix.str_pad((string) $number, $width, '0', STR_PAD_LEFT);
        } while (Product::query()->withoutGlobalScopes()->where('product_code', $code)->exists());

        return $code;
    }
}
