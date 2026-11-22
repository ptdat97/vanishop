<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\StoreLocale;
use Plugin\Lookbook\Infrastructure\Lookbooks;
use Plugin\Lookbook\Persistence\Lookbook;
use Plugin\SizeChart\Infrastructure\SizeCharts;
use Plugin\SizeChart\Persistence\SizeChart;

/**
 * Nội dung của plugin (chỉ khi plugin đang bật): trang chính sách, tin tức (chuyên mục, ảnh bìa, sản phẩm trong bài),
 * thanh thông báo xoay vòng, hộp ưu đãi + điểm nổi bật ở trang sản phẩm, banner danh mục, lookbook, bảng size mặc định.
 */
final class ContentSeeder extends Seeder
{
    public function run(Extensions $extensions): void
    {
        foreach ($extensions->tagged(DemoContent::TAG) as $content) {
            if ($content instanceof DemoContent) {
                $content->seed();
            }
        }
        if (self::enabled('vani.lookbook')) {
            $this->lookbooks();
        }
        if (self::enabled('vani.size-chart') && ! SizeChart::query()->exists()) {
            app(SizeCharts::class)->save([
                'names' => [StoreLocale::default() => 'Bảng size nữ Vani Studio'], 'base_unit' => 'cm', 'display_unit' => null,
                'blocks' => [
                    ['type' => 'table', 'rows' => [['Size', 'Ngực', 'Eo', 'Mông', 'Chiều cao'], ['S', '80–84', '62–66', '86–90', '150–158'], ['M', '84–88', '66–70', '90–94', '155–163'], ['L', '88–92', '70–74', '94–98', '160–168']]],
                    ['type' => 'text', 'markdown' => 'Đo sát người, thước dây không siết. **Giữa hai size, chọn size lớn hơn** với dáng ôm.'],
                ],
                'advisor_enabled' => true, 'advisor_rules' => [
                    ['size' => 'S', 'height_min' => 148, 'height_max' => 158, 'weight_min' => 40, 'weight_max' => 48, 'note' => ''],
                    ['size' => 'M', 'height_min' => 155, 'height_max' => 165, 'weight_min' => 47, 'weight_max' => 55, 'note' => ''],
                    ['size' => 'L', 'height_min' => 160, 'height_max' => 172, 'weight_min' => 54, 'weight_max' => 63, 'note' => ''],
                ],
                'is_default' => true, 'active' => true,
            ]);
        }
    }

    public static function enabled(string $pluginId): bool
    {
        return vani_plugin($pluginId)?->enabled === true;
    }

    private function lookbooks(): void
    {
        $images = DemoCatalog::byStyle();
        $all = array_merge(...array_values($images ?: [[]]));
        $looks = [
            'thu-dong-2026' => ['Thu Đông 2026', 'Bộ ảnh mùa se lạnh: **len, dạ và lụa** trong tông be — navy.', 'ao', 0],
            'he-ruc-ro' => ['Hè rực rỡ', 'Đầm maxi, chân váy midi và những gam màu tươi sáng.', 'dam', 8],
        ];
        $position = 0;
        foreach ($looks as $slug => [$name, $description, $category, $offset]) {
            $pictures = array_slice($all, $offset, 8);
            if ($pictures === [] || Lookbook::query()->where('slug', $slug)->exists()) {
                continue;
            }
            app(Lookbooks::class)->save(null, [
                'slug' => $slug, 'translations' => [StoreLocale::default() => ['name' => $name, 'description' => $description]],
                'cover_media_id' => $pictures[0], 'category_id' => Category::query()->where('slug', $category)->value('id'),
                'position' => $position++, 'active' => true,
                'images' => array_map(fn (int $id): array => ['media_id' => $id], $pictures),
            ]);
        }
    }
}
