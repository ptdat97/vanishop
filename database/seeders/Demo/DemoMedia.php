<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Modules\Catalog\Persistence\Models\Style;

/**
 * Ảnh của sản phẩm demo (Thư viện ảnh) để dùng lại cho trình chiếu, banner, lookbook, bài viết.
 */
final class DemoMedia
{
    /**
     * Mã sản phẩm → id ảnh theo thứ tự, chỉ sản phẩm "Vani Studio" đang bán.
     *
     * @return array<string, list<int>>
     */
    public static function byStyle(): array
    {
        $result = [];
        $styles = Style::query()->where('style_code', 'like', 'VS-%')->where('status', 'active')->with('colors.gallery')->orderBy('id')->get();
        foreach ($styles as $style) {
            $ids = $style->colors->flatMap(fn ($color) => $color->gallery->pluck('media_id'))->map(fn ($id): int => (int) $id)->values()->all();
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
