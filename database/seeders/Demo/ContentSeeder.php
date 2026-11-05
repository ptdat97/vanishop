<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cms\Infrastructure\CmsMedia;
use Plugin\Cms\Infrastructure\ExtraContent;
use Plugin\Cms\Persistence\Category as NewsCategory;
use Plugin\Cms\Persistence\CategoryBanner;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;
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
    public function run(Settings $settings, MediaDirectory $media): void
    {
        if (self::enabled('vani.cms')) {
            $this->pages();
            $this->posts($media);
            $this->extraContent($settings);
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

    private function pages(): void
    {
        $pages = [
            'gioi-thieu' => ['Giới thiệu Vani Studio', "Vani Studio là thương hiệu thời trang nữ thiết kế tại Việt Nam.\n\n## Chúng tôi làm gì\n\n- Thiết kế **tối giản, dễ phối**, mặc được nhiều mùa.\n- Chất liệu chọn kỹ: lụa, linen, len mỏng.\n- May tại xưởng ở TP. Hồ Chí Minh.", false],
            'chinh-sach-doi-tra' => ['Chính sách đổi trả', "## Đổi trả trong 7 ngày\n\n1. Sản phẩm còn tem mác, chưa giặt.\n2. Yêu cầu đổi trả trong **Tài khoản → Đơn hàng**, kèm ảnh nếu hàng lỗi.\n3. Hàng lỗi do sản xuất: Vani chịu phí vận chuyển hai chiều.", true],
            'huong-dan-chon-size' => ['Hướng dẫn chọn size', "Xem **Bảng size** ở trang từng sản phẩm, hoặc nhập chiều cao/cân nặng để được gợi ý size.\n\n| Size | Ngực | Eo |\n|---|---|---|\n| S | 80–84 | 62–66 |\n| M | 84–88 | 66–70 |\n| L | 88–92 | 70–74 |", true],
            'chinh-sach-bao-mat' => ['Chính sách bảo mật', 'Vani chỉ dùng số điện thoại, email, địa chỉ của bạn để giao hàng và hỗ trợ đơn. Bạn có thể xoá tài khoản bất cứ lúc nào trong **Tài khoản → Cài đặt**.', true],
        ];
        $position = 0;
        foreach ($pages as $slug => [$title, $body, $footer]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title, 'body' => $body, 'status' => 'published', 'published_at' => now()->subDays(30),
                'show_in_header' => false, 'show_in_footer' => $footer, 'sort_order' => $position++,
            ]);
        }
    }

    private function posts(MediaDirectory $media): void
    {
        $categories = [];
        foreach (['xu-huong' => 'Xu hướng', 'phoi-do' => 'Phối đồ'] as $slug => $name) {
            $categories[$slug] = NewsCategory::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => count($categories)])->id;
        }
        $covers = DemoMedia::covers(4, 2);
        $styles = Style::query()->where('style_code', 'like', 'VS-%')->orderBy('id')->pluck('id')->all();
        $posts = [
            ['5-cach-phoi-dam-lua-di-lam', '5 cách phối đầm lụa đi làm', 'phoi-do', 'Đầm lụa không chỉ để đi tiệc: thêm blazer, giày bệt là sẵn sàng cho văn phòng.', "## 1. Thêm blazer dáng dài\n\nBlazer trung tính giúp đầm lụa **đứng dáng** hơn.\n\n## 2. Giày bệt mũi nhọn\n\nThoải mái cả ngày mà vẫn thanh lịch."],
            ['xu-huong-thu-dong-2026', 'Xu hướng Thu Đông 2026', 'xu-huong', 'Gam be, navy và chất liệu len mỏng lên ngôi mùa này.', "Mùa Thu Đông 2026 ưu tiên **tông trung tính** và chất liệu mềm.\n\n- Len dệt kim mỏng\n- Dạ dáng ngắn\n- Lụa satin màu trầm"],
            ['bi-quyet-chon-chan-vay', 'Bí quyết chọn chân váy theo dáng người', 'phoi-do', 'Chữ A, bút chì hay xếp ly — chọn đúng dáng giúp bạn cao và thon hơn.', 'Người thấp nên chọn **chân váy chữ A ngang gối**; dáng cao mặc midi xếp ly rất hợp.'],
        ];
        foreach ($posts as $index => [$slug, $title, $category, $excerpt, $body]) {
            if (Post::query()->where('slug', $slug)->exists()) {
                continue;
            }
            $post = Post::query()->create([
                'slug' => $slug, 'title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'status' => 'published',
                'published_at' => now()->subDays(10 - $index * 3), 'category_id' => $categories[$category],
                'cover_media_id' => $covers[$index] ?? null, 'product_ids' => array_slice($styles, $index * 3, 3),
            ]);
            if ($post->cover_media_id !== null) {
                $media->syncUsages(CmsMedia::POST, $post->id, 'cover', [$post->cover_media_id]);
            }
        }
    }

    private function extraContent(Settings $settings): void
    {
        $locale = StoreLocale::default();
        // Chỉ khi cửa hàng chưa tự cấu hình (get() trả mặc định nên dùng explicit()).
        if (! array_key_exists('announcement_enabled', $settings->explicit('vani.cms'))) {
            $settings->set('vani.cms', 'announcement_enabled', true);
            $settings->set('vani.cms', "announcement_lines.{$locale}", "Miễn phí giao hàng cho đơn từ **500.000 ₫**\nNhập mã **CHAO10** giảm 10% đơn đầu tiên\nĐổi trả trong 7 ngày — [xem chính sách](/trang/chinh-sach-doi-tra)");
            $settings->set('vani.cms', 'promo_box_enabled', true);
            $settings->set('vani.cms', "promo_box_markdown.{$locale}", '**Ưu đãi:** nhập mã **GIAM50K** giảm 50.000 ₫ · Miễn phí giao từ 500.000 ₫ · Đổi size miễn phí trong 7 ngày.');
        }

        $content = app(ExtraContent::class);
        foreach (array_slice(Style::query()->where('style_code', 'like', 'VS-%')->orderBy('id')->pluck('id')->all(), 0, 8) as $styleId) {
            if ($content->productForm($styleId)[ExtraContent::highlightKey($locale)] === '') {
                $content->saveProduct($styleId, [ExtraContent::highlightKey($locale) => "Chất liệu mềm, thoáng mát\nĐường may tỉ mỉ, không xù\nĐổi size miễn phí trong 7 ngày"]);
            }
        }

        $banners = ['dam' => ['Đầm cho mọi dịp', 'Từ công sở tới dạo phố — **giảm 20%** nhiều mẫu.', 0], 'ao' => ['Áo dễ phối', 'Sơ mi lụa, len mỏng, blazer dáng dài.', 3]];
        foreach ($banners as $slug => [$title, $markdown, $offset]) {
            $category = Category::query()->where('slug', $slug)->value('id');
            if ($category === null || CategoryBanner::query()->where('category_id', $category)->exists()) {
                continue;
            }
            $content->saveBanner(null, (int) $category, [
                'content' => [$locale => ['title' => $title, 'markdown' => $markdown, 'button_text' => 'Xem bộ sưu tập']],
                'button_url' => '/tim-kiem?collection=thu-dong-2026', 'desktop_media_id' => DemoMedia::covers(1, $offset)[0] ?? null, 'active' => true,
            ]);
        }
    }

    private function lookbooks(): void
    {
        $images = DemoMedia::byStyle();
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
