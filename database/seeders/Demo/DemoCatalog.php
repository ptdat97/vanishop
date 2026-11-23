<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;

/**
 * Danh mục, sản phẩm và ảnh từ catalog dùng cho nội dung demo.
 */
final class DemoCatalog
{
    /** @return list<int> */
    public static function styleIds(): array
    {
        return Style::query()->where('style_code', 'like', 'VS-%')->orderBy('id')->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    public static function categoryId(string $slug): ?int
    {
        return Category::query()->where('slug', $slug)->first(['id'])?->id;
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, int>
     */
    public static function activeStyleIdsByCode(array $codes): array
    {
        return Style::query()->whereIn('style_code', $codes)->where('status', 'active')->pluck('id', 'style_code')->all();
    }

    /**
     * Mã sản phẩm → id ảnh theo thứ tự, chỉ sản phẩm "Vani Studio" đang bán.
     *
     * @return array<string, list<int>>
     */
    public static function byStyle(): array
    {
        $result = [];
        $styles = Style::query()->where('style_code', 'like', 'VS-%')->where('status', 'active')->with('variants.gallery')->orderBy('id')->get();
        foreach ($styles as $style) {
            $ids = $style->variants->flatMap(fn ($variant) => $variant->gallery->pluck('media_id'))->unique()->map(fn ($id): int => (int) $id)->values()->all();
            if ($ids !== []) {
                $result[$style->style_code] = $ids;
            }
        }

        return $result;
    }

    /**
     * Ảnh đầu tiên của N sản phẩm đầu.
     *
     * @return list<int>
     */
    public static function covers(int $count, int $offset = 0): array
    {
        return array_slice(array_map(fn (array $ids): int => $ids[0], array_values(self::byStyle())), $offset, $count);
    }
}
