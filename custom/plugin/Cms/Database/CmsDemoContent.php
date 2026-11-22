<?php

declare(strict_types=1);

namespace Plugin\Cms\Database;

use Database\Seeders\Demo\DemoCatalog;
use Database\Seeders\Demo\DemoContent;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cms\Infrastructure\CmsMedia;
use Plugin\Cms\Infrastructure\ExtraContent;
use Plugin\Cms\Persistence\Category as NewsCategory;
use Plugin\Cms\Persistence\CategoryBanner;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

final class CmsDemoContent implements DemoContent
{
    public function __construct(private readonly Settings $settings, private readonly MediaDirectory $media) {}

    public function seed(): void
    {
        $this->pages();
        $styleIds = DemoCatalog::styleIds();
        $this->posts($this->media, $styleIds);
        $this->extraContent($this->settings, $styleIds);
    }

    public function links(): array
    {
        $slugs = [
            'about' => 'gioi-thieu', 'returns' => 'chinh-sach-doi-tra',
            'size_guide' => 'huong-dan-chon-size', 'privacy' => 'chinh-sach-bao-mat',
        ];
        $pages = Page::query()->whereIn('slug', array_values($slugs))->pluck('id', 'slug');
        $links = ['news' => ['type' => 'url', 'value' => '/tin-tuc']];
        foreach ($slugs as $key => $slug) {
            if (isset($pages[$slug])) {
                $links[$key] = ['type' => 'page', 'value' => (string) $pages[$slug]];
            }
        }

        return $links;
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

    /** @param list<int> $styles */
    private function posts(MediaDirectory $media, array $styles): void
    {
        $categories = [];
        foreach (['xu-huong' => 'Xu hướng', 'phoi-do' => 'Phối đồ'] as $slug => $name) {
            $categories[$slug] = NewsCategory::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => count($categories)])->id;
        }
        $covers = DemoCatalog::covers(4, 2);
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

    /** @param list<int> $styleIds */
    private function extraContent(Settings $settings, array $styleIds): void
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
        foreach (array_slice($styleIds, 0, 8) as $styleId) {
            if ($content->productForm($styleId)[ExtraContent::highlightKey($locale)] === '') {
                $content->saveProduct($styleId, [ExtraContent::highlightKey($locale) => "Chất liệu mềm, thoáng mát\nĐường may tỉ mỉ, không xù\nĐổi size miễn phí trong 7 ngày"]);
            }
        }

        $banners = ['dam' => ['Đầm cho mọi dịp', 'Từ công sở tới dạo phố — **giảm 20%** nhiều mẫu.', 0], 'ao' => ['Áo dễ phối', 'Sơ mi lụa, len mỏng, blazer dáng dài.', 3]];
        foreach ($banners as $slug => [$title, $markdown, $offset]) {
            $category = DemoCatalog::categoryId($slug);
            if ($category === null || CategoryBanner::query()->where('category_id', $category)->exists()) {
                continue;
            }
            $content->saveBanner(null, (int) $category, [
                'content' => [$locale => ['title' => $title, 'markdown' => $markdown, 'button_text' => 'Xem bộ sưu tập']],
                'button_url' => '/bo-suu-tap/thu-dong-2026', 'desktop_media_id' => DemoCatalog::covers(1, $offset)[0] ?? null, 'active' => true,
            ]);
        }
    }
}
