<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Menus\MenuRepository;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Application\Theme\ThemeSettings;
use Plugin\Cms\Persistence\Page;

/**
 * Giao diện: trang chủ (trình chiếu, cam kết, hàng mới, lưới banner, tab sản phẩm, bán chạy, lookbook, tin tức, đã xem),
 * menu đầu trang / di động / chân trang, thông tin chân trang của theme. Chỉ ghi khi cửa hàng chưa cấu hình phần đó.
 */
final class StorefrontSeeder extends Seeder
{
    public function run(PageBlocks $blocks, MenuRepository $menus, ThemeSettings $theme): void
    {
        if ($blocks->home() === null) {
            $blocks->saveHome($this->homeBlocks($blocks));
        }
        foreach ($this->menus() as $location => $items) {
            if (array_key_exists($location, $menus->locations()) && $menus->published($location) === null) {
                $menus->saveDraft($location, $items);
                $menus->publish($location);
            }
        }
        if (($theme->stored()['footer'] ?? []) === []) {
            $theme->save(['header' => ['announcement' => [], 'announcement_url' => null], 'footer' => [
                'contact_phone' => '1900 6868', 'contact_email' => 'hotro@vanistudio.vn', 'contact_address' => '12 Lê Lợi, Phường Bến Thành, TP. Hồ Chí Minh',
                'facebook_url' => 'https://facebook.com/vanistudio', 'instagram_url' => 'https://instagram.com/vanistudio', 'tiktok_url' => null, 'youtube_url' => null,
                'zalo_url' => 'https://zalo.me/19006868', 'payment_methods' => "COD\nChuyển khoản\nVNPay", 'copyright' => [StoreLocale::default() => '© Vani Studio — dữ liệu demo'],
            ]]);
        }
    }

    /**
     * @return list<array{type: string, config: array<string, mixed>}>
     */
    private function homeBlocks(PageBlocks $blocks): array
    {
        $available = $blocks->types();
        // Ảnh từ Thư viện ảnh (id) — khối tự có bản vừa màn hình.
        $images = DemoMedia::covers(6);
        $image = fn (int $index): ?int => $images[$index] ?? null;

        $candidates = [
            ['extras_slideshow', ['interval' => 5,
                'slide_1_media' => $image(0), 'slide_1_title' => 'Thu Đông 2026', 'slide_1_text' => 'Len, dạ và lụa cho mùa se lạnh', 'slide_1_link' => '/tim-kiem?collection=thu-dong-2026', 'slide_1_label' => 'Khám phá',
                'slide_2_media' => $image(1), 'slide_2_title' => 'Đầm cho mọi dịp', 'slide_2_text' => 'Giảm đến 20% nhiều mẫu', 'slide_2_link' => '/danh-muc/dam', 'slide_2_label' => 'Mua ngay',
                'slide_3_media' => $image(2), 'slide_3_title' => 'Công sở thanh lịch', 'slide_3_text' => 'Chỉn chu mà vẫn thoải mái', 'slide_3_link' => '/tim-kiem?collection=cong-so-thanh-lich', 'slide_3_label' => 'Xem bộ sưu tập']],
            ['hero', ['title' => 'Thu Đông 2026', 'subtitle' => 'Len, dạ và lụa cho mùa se lạnh.', 'image' => $image(0), 'link_label' => 'Khám phá', 'link_url' => '/danh-muc/hang-moi-ve']],
            ['extras_features', ['item_1_icon' => 'truck', 'item_1_title' => 'Giao nhanh 2h', 'item_1_text' => 'Nội thành TP. HCM',
                'item_2_icon' => 'return', 'item_2_title' => 'Đổi trả 7 ngày', 'item_2_text' => 'Đổi size miễn phí',
                'item_3_icon' => 'shield', 'item_3_title' => 'Thanh toán an toàn', 'item_3_text' => 'COD, chuyển khoản, VNPay',
                'item_4_icon' => 'support', 'item_4_title' => 'Tư vấn chọn size', 'item_4_text' => 'Hotline 1900 6868']],
            ['product_grid', ['title' => 'Hàng mới về', 'source' => 'category', 'slug' => CatalogSeeder::NEW_ARRIVALS, 'limit' => 8]],
            ['extras_banner_grid', ['layout' => '3',
                'cell_1_media' => $image(3), 'cell_1_title' => 'Đầm', 'cell_1_link' => '/danh-muc/dam',
                'cell_2_media' => $image(4), 'cell_2_title' => 'Áo', 'cell_2_link' => '/danh-muc/ao',
                'cell_3_media' => $image(5), 'cell_3_title' => 'Chân váy', 'cell_3_link' => '/danh-muc/chan-vay']],
            ['extras_product_tabs', ['title' => 'Gợi ý cho bạn', 'limit' => 8,
                'tab_1_label' => 'Đầm', 'tab_1_source' => 'category', 'tab_1_value' => 'dam',
                'tab_2_label' => 'Áo', 'tab_2_source' => 'category', 'tab_2_value' => 'ao',
                'tab_3_label' => 'Chân váy', 'tab_3_source' => 'category', 'tab_3_value' => 'chan-vay']],
            ['extras_bestsellers', ['title' => 'Bán chạy', 'limit' => 4, 'days' => '30']],
            ['lookbook_latest', ['title' => 'Lookbook', 'limit' => 3]],
            ['cms_latest_posts', ['title' => 'Tin tức & phối đồ', 'limit' => 3]],
            ['extras_recently_viewed', ['title' => 'Bạn đã xem', 'limit' => 8, 'layout' => 'scroll']],
        ];

        $result = [];
        foreach ($candidates as [$type, $config]) {
            // Trình chiếu thay hero khi có plugin vani.storefront-extras.
            if (! isset($available[$type]) || ($type === 'hero' && isset($available['extras_slideshow']))) {
                continue;
            }
            $result[] = ['type' => $type, 'config' => $config];
        }

        return $result;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function menus(): array
    {
        $category = fn (string $slug): ?array => ($id = Category::query()->where('slug', $slug)->value('id')) === null ? null : ['type' => 'category', 'value' => (string) $id];
        $item = fn (?array $link, ?string $label = null, array $children = [], ?string $badge = null): array => [
            'id' => (string) Str::ulid(), 'link' => $link, 'label' => $label === null ? [] : [StoreLocale::default() => $label],
            'badge' => $badge, 'image_media_id' => null, 'enabled' => true, 'visible_from' => null, 'visible_to' => null, 'children' => $children,
        ];

        $women = array_values(array_filter(array_map(fn (string $slug): ?array => ($link = $category($slug)) === null ? null : $item($link), array_keys(CatalogSeeder::CATEGORIES))));
        $collections = ProductCollection::query()->orderBy('position')->pluck('id')->map(fn ($id): array => $item(['type' => 'collection', 'value' => (string) $id]))->all();

        $header = array_values(array_filter([
            ($link = $category(CatalogSeeder::NEW_ARRIVALS)) === null ? null : $item($link, null, [], 'new'),
            ($link = $category('nu')) === null ? null : $item($link, null, $women),
            $collections === [] ? null : $item(null, 'Bộ sưu tập', $collections),
            ContentSeeder::enabled('vani.lookbook') ? $item(['type' => 'url', 'value' => '/lookbook'], 'Lookbook') : null,
            ContentSeeder::enabled('vani.cms') ? $item(['type' => 'url', 'value' => '/tin-tuc'], 'Tin tức') : null,
        ]));

        $page = fn (string $slug, string $label): ?array => ContentSeeder::enabled('vani.cms') && ($id = Page::query()->where('slug', $slug)->value('id')) !== null
            ? $item(['type' => 'page', 'value' => (string) $id], $label) : null;
        $support = array_values(array_filter([
            $item(['type' => 'route', 'value' => 'storefront.track'], 'Tra cứu đơn hàng'),
            $page('chinh-sach-doi-tra', 'Đổi trả'), $page('huong-dan-chon-size', 'Chọn size'), $page('chinh-sach-bao-mat', 'Bảo mật'),
        ]));
        $about = array_values(array_filter([
            $page('gioi-thieu', 'Giới thiệu'),
            ContentSeeder::enabled('vani.cms') ? $item(['type' => 'url', 'value' => '/tin-tuc'], 'Tin tức') : null,
            $item(['type' => 'route', 'value' => 'storefront.brands'], 'Thương hiệu'),
        ]));

        return [
            'header' => $header,
            // Menu di động tối đa 2 cấp: bỏ cấp con của cấp con.
            'mobile' => array_map(fn (array $entry): array => [...$entry, 'id' => (string) Str::ulid(), 'children' => array_map(fn (array $child): array => [...$child, 'children' => []], $entry['children'])], $header),
            // Cột chân trang một cấp: tiêu đề cột là tên vị trí trong theme.
            'footer_1' => $support,
            'footer_2' => $about,
        ];
    }
}
