<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Application\Menus\MenuRepository;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Application\Theme\ThemeSettings;

/**
 * Giao diện: trang chủ (trình chiếu, cam kết, hàng mới, lưới banner, tab sản phẩm, bán chạy, lookbook, tin tức, đã xem),
 * nội dung Builder Engine trên slot của trang sản phẩm, danh sách, giỏ trống, thương hiệu; menu đầu trang / di động /
 * chân trang, thông tin chân trang của theme. Nội dung riêng từng bộ sưu tập do plugin vani.catalog-pages. Chỉ ghi khi cửa hàng chưa cấu hình
 * phần đó. Block của plugin chỉ dùng khi plugin đang bật, không thì dùng block Core thay thế.
 */
final class StorefrontSeeder extends Seeder
{
    /** Surface nội dung riêng từng danh mục/thương hiệu/bộ sưu tập của plugin vani.catalog-pages. */
    private const CATALOG_PAGES = 'catalog-pages';

    public function run(BuilderEngine $builder, PageBlocks $blocks, MenuRepository $menus, ThemeSettings $theme): void
    {
        if ($builder->blocks(BuilderEngine::HOME) === null) {
            $builder->save(BuilderEngine::HOME, $this->homeBlocks($blocks));
        }
        foreach ($this->surfaceBlocks(array_keys($blocks->types())) as $surface => $surfaceBlocks) {
            if ($surfaceBlocks !== [] && $builder->surface($surface) !== null && $builder->blocks($surface) === null) {
                $builder->save($surface, $surfaceBlocks);
            }
        }
        $this->seedCollectionPages($builder, array_keys($blocks->types()));
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
        $images = DemoCatalog::covers(6);
        $image = fn (int $index): ?int => $images[$index] ?? null;

        $candidates = [
            ['extras_slideshow', ['interval' => 5,
                'slide_1_media' => $image(0), 'slide_1_title' => 'Thu Đông 2026', 'slide_1_text' => 'Len, dạ và lụa cho mùa se lạnh', 'slide_1_link' => '/bo-suu-tap/thu-dong-2026', 'slide_1_label' => 'Khám phá',
                'slide_2_media' => $image(1), 'slide_2_title' => 'Đầm cho mọi dịp', 'slide_2_text' => 'Giảm đến 20% nhiều mẫu', 'slide_2_link' => '/danh-muc/dam', 'slide_2_label' => 'Mua ngay',
                'slide_3_media' => $image(2), 'slide_3_title' => 'Công sở thanh lịch', 'slide_3_text' => 'Chỉn chu mà vẫn thoải mái', 'slide_3_link' => '/bo-suu-tap/cong-so-thanh-lich', 'slide_3_label' => 'Xem bộ sưu tập']],
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
     * Nội dung Builder Engine trên slot có sẵn, theo surface. Khoá `zone` là tên slot.
     *
     * @param  list<string>  $available  loại block đang có
     * @return array<string, list<array{type: string, zone: string, config: array<string, mixed>}>>
     */
    private function surfaceBlocks(array $available): array
    {
        $has = fn (string $type): bool => in_array($type, $available, true);
        $images = DemoCatalog::covers(4, 6);
        $image = fn (int $index): ?int => $images[$index] ?? null;
        $block = fn (string $zone, string $type, array $config): array => ['type' => $type, 'zone' => "vani.storefront.{$zone}", 'config' => $config];
        $newest = fn (string $zone, string $title, int $limit = 4): array => $block($zone, 'product_grid', ['title' => $title, 'source' => 'category', 'slug' => CatalogSeeder::NEW_ARRIVALS, 'limit' => $limit]);

        return [
            'product' => array_values(array_filter([
                $block('pdp.after_details', 'rich_text', ['title' => 'Cam kết của Vani Studio', 'body' => self::doc([
                    self::bullets(['Kiểm tra hàng trước khi thanh toán COD', 'Đổi size miễn phí trong 7 ngày', 'May tại xưởng Vani, kiểm từng đường chỉ']),
                ])]),
                $has('extras_features')
                    ? $block('pdp.after_related_products', 'extras_features', [
                        'item_1_icon' => 'truck', 'item_1_title' => 'Giao nhanh 2h', 'item_1_text' => 'Đơn nội thành TP. HCM trước 16h',
                        'item_2_icon' => 'return', 'item_2_title' => 'Đổi trả dễ dàng', 'item_2_text' => 'Đổi tại cửa hàng hoặc gửi lại qua bưu cục',
                        'item_3_icon' => 'shield', 'item_3_title' => 'Hàng chính hãng', 'item_3_text' => 'Tem và hoá đơn cho mọi đơn hàng',
                        'item_4_icon' => 'support', 'item_4_title' => 'Stylist tư vấn', 'item_4_text' => 'Chat để được gợi ý phối đồ'])
                    : $newest('pdp.after_related_products', 'Hàng mới về'),
            ])),
            'listing' => array_values(array_filter([
                $has('extras_banner_grid') && $image(1) !== null
                    ? $block('plp.after_listing', 'extras_banner_grid', ['layout' => '2',
                        'cell_1_media' => $image(0), 'cell_1_title' => 'Thu Đông 2026', 'cell_1_link' => '/bo-suu-tap/thu-dong-2026',
                        'cell_2_media' => $image(1), 'cell_2_title' => 'Công sở thanh lịch', 'cell_2_link' => '/bo-suu-tap/cong-so-thanh-lich'])
                    : null,
                $block('plp.empty', 'rich_text', ['title' => 'Chưa có sản phẩm phù hợp', 'body' => self::doc([
                    self::paragraph('Thử bỏ bớt bộ lọc, chọn size khác hoặc xem các mẫu mới về bên dưới.'),
                ])]),
                $newest('plp.empty', 'Có thể bạn sẽ thích'),
            ])),
            'cart' => [
                $has('extras_bestsellers')
                    ? $block('cart.empty', 'extras_bestsellers', ['title' => 'Khách hàng đang mua', 'limit' => 4, 'days' => '30'])
                    : $newest('cart.empty', 'Khách hàng đang mua'),
                $newest('cart.empty', 'Hàng mới về'),
            ],
            'brands' => [
                $block('brands.before_list', 'rich_text', ['body' => self::doc([
                    self::paragraph('Thương hiệu được chọn lọc cho tủ đồ công sở và dạo phố. Chọn thương hiệu để xem toàn bộ sản phẩm.'),
                ])]),
                $block('brands.after_list', 'product_grid', ['title' => 'Nổi bật từ '.CatalogSeeder::BRAND['name'], 'source' => 'brand', 'slug' => CatalogSeeder::BRAND['slug'], 'limit' => 8]),
            ],
        ];
    }

    /**
     * Nội dung riêng của bộ sưu tập demo (plugin vani.catalog-pages, khi đang bật): banner Hero ở đầu trang, câu chuyện
     * trên lưới, gợi ý dưới lưới. Bỏ qua bộ sưu tập đã có nội dung riêng.
     *
     * @param  list<string>  $available  loại block đang có
     */
    private function seedCollectionPages(BuilderEngine $builder, array $available): void
    {
        if ($builder->surface(self::CATALOG_PAGES) === null) {
            return;
        }
        $content = [
            'thu-dong-2026' => [
                ['hero', 'header', ['title' => 'Thu Đông 2026', 'subtitle' => 'Len mềm, dạ dày dặn và lụa nhẹ cho những ngày se lạnh.']],
                ['rich_text', 'before_listing', ['title' => 'Chất liệu mùa này', 'body' => self::doc([
                    self::paragraph('Bảng màu trầm ấm lấy cảm hứng từ phố cổ Hà Nội cuối thu.'),
                    self::bullets(['Len merino pha cashmere, không gây ngứa', 'Dạ tweed dệt tay cho áo khoác', 'Lụa tơ tằm cho lớp trong'])])]],
                in_array('lookbook_latest', $available, true)
                    ? ['lookbook_latest', 'after_listing', ['title' => 'Phối đồ Thu Đông', 'limit' => 3]]
                    : ['product_grid', 'after_listing', ['title' => 'Công sở thanh lịch', 'source' => 'collection', 'slug' => 'cong-so-thanh-lich', 'limit' => 4]],
            ],
            'cong-so-thanh-lich' => [
                ['hero', 'header', ['title' => 'Công sở thanh lịch', 'subtitle' => 'Chỉn chu từ cuộc họp sáng đến buổi hẹn tối.']],
                ['rich_text', 'before_listing', ['title' => 'Một tuần, năm cách phối', 'body' => self::doc([
                    self::bullets(['Chân váy bút chì cùng sơ mi lụa', 'Quần ống suông với áo len mỏng', 'Đầm suông thêm blazer khi cần'], 'orderedList')])]],
                ['product_grid', 'after_listing', ['title' => 'Thêm từ Thu Đông 2026', 'source' => 'collection', 'slug' => 'thu-dong-2026', 'limit' => 4]],
            ],
        ];
        foreach (ProductCollection::query()->whereIn('slug', array_keys($content))->get(['id', 'slug']) as $collection) {
            $scope = "collection-{$collection->id}";
            if ($builder->blocks(self::CATALOG_PAGES, $scope) !== null) {
                continue;
            }
            $builder->save(self::CATALOG_PAGES, array_map(fn (array $block): array => [
                'type' => $block[0], 'zone' => "vani.storefront.plp.{$block[1]}", 'config' => $block[2],
            ], $content[$collection->slug]), $scope);
        }
    }

    /** @param  list<array<string, mixed>>  $content */
    private static function doc(array $content): string
    {
        return json_encode(['type' => 'doc', 'content' => $content], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, mixed> */
    private static function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * @param  list<string>  $items
     * @return array<string, mixed>
     */
    private static function bullets(array $items, string $type = 'bulletList'): array
    {
        return ['type' => $type, 'content' => array_map(fn (string $item): array => ['type' => 'listItem', 'content' => [self::paragraph($item)]], $items)];
    }

    /**
     * Menu di động (danh mục chia đôi của vani-base, 3 cấp, ảnh mọi cấp): cấp 1 = cột trái (ảnh = banner), cấp 2 có con =
     * tiêu đề nhóm, mục lá = ô lưới có ảnh; cấp 1 không con (Lookbook, Tin tức) = link thẳng. Ảnh lấy từ sản phẩm demo.
     * Tạo lại trên cửa hàng đã có menu: `saveDraft('mobile', app(StorefrontSeeder::class)->mobileMenu())` rồi `publish`.
     *
     * @return list<array<string, mixed>>
     */
    public function mobileMenu(): array
    {
        $covers = DemoCatalog::byStyle();
        $used = [];
        // Ảnh đầu tiên chưa dùng của các sản phẩm thoả điều kiện — mỗi ô một ảnh khác nhau.
        $cover = function (callable $scope) use ($covers, &$used): ?int {
            $codes = $scope(Style::query()->where('style_code', 'like', 'VS-%')->where('status', 'active'))->orderBy('id')->pluck('style_code');
            foreach ($codes as $code) {
                foreach ($covers[$code] ?? [] as $mediaId) {
                    if (! isset($used[$mediaId])) {
                        $used[$mediaId] = true;

                        return $mediaId;
                    }
                }
            }

            return null;
        };
        $anyCover = fn (): ?int => $cover(fn ($query) => $query);
        $item = fn (?array $link, ?string $label = null, array $children = [], ?int $image = null, ?string $badge = null): array => [
            'id' => (string) Str::ulid(), 'link' => $link, 'label' => $label === null ? [] : [StoreLocale::default() => $label],
            'badge' => $badge, 'image_media_id' => $image, 'enabled' => true, 'visible_from' => null, 'visible_to' => null, 'children' => $children,
        ];
        $categoryId = fn (string $slug): ?int => ($id = Category::query()->where('slug', $slug)->value('id')) === null ? null : (int) $id;
        $categoryItem = fn (string $slug, ?string $badge = null): ?array => ($id = $categoryId($slug)) === null ? null
            : $item(['type' => 'category', 'value' => (string) $id], null, [], $cover(fn ($query) => $query->where('primary_category_id', $id)) ?? $anyCover(), $badge);
        $productItem = fn (Style $style): array => $item(['type' => 'product', 'value' => (string) $style->id], null, [], $cover(fn ($query) => $query->whereKey($style->id)));

        $clothing = array_values(array_filter(array_map(fn (string $slug): ?array => $categoryItem($slug), array_keys(CatalogSeeder::CATEGORIES))));
        $newest = Style::query()->where('style_code', 'like', 'VS-%')->where('status', 'active')->latest('id')->limit(6)->get()->map($productItem)->all();
        $collections = ProductCollection::query()->orderBy('position')->get()
            ->map(fn (ProductCollection $collection): array => $item(['type' => 'collection', 'value' => (string) $collection->id], null, [],
                $cover(fn ($query) => $query->whereHas('collections', fn ($inner) => $inner->whereKey($collection->id))) ?? $anyCover()))->all();
        $brands = Brand::query()->where('status', Brand::ACTIVE)->orderBy('position')->get()
            ->map(fn (Brand $brand): array => $item(['type' => 'brand', 'value' => (string) $brand->id], null, [],
                $cover(fn ($query) => $query->where('brand_id', $brand->id)) ?? $anyCover()))->all();

        $women = $categoryId('nu');
        $newArrivals = $categoryId(CatalogSeeder::NEW_ARRIVALS);
        $content = $this->contentLinks();

        return array_values(array_filter([
            $newArrivals === null ? null : $item(['type' => 'category', 'value' => (string) $newArrivals], null, $newest, $anyCover(), 'new'),
            $women === null ? null : $item(['type' => 'category', 'value' => (string) $women], null, array_values(array_filter([
                $clothing === [] ? null : $item(['type' => 'category', 'value' => (string) $women], 'Trang phục', $clothing),
                $collections === [] ? null : $item(null, 'Theo bộ sưu tập', $collections),
            ])), $anyCover()),
            $collections === [] ? null : $item(null, 'Bộ sưu tập', array_map(fn (array $entry): array => [...$entry, 'id' => (string) Str::ulid(), 'image_media_id' => $anyCover() ?? $entry['image_media_id']], $collections), $anyCover()),
            $brands === [] ? null : $item(['type' => 'route', 'value' => 'storefront.brands'], 'Thương hiệu', $brands, $anyCover()),
            ContentSeeder::enabled('vani.lookbook') ? $item(['type' => 'url', 'value' => '/lookbook'], 'Lookbook') : null,
            isset($content['news']) ? $item($content['news'], 'Tin tức') : null,
        ]));
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
        $content = $this->contentLinks();
        $page = fn (string $key, string $label): ?array => isset($content[$key]) ? $item($content[$key], $label) : null;

        $header = array_values(array_filter([
            ($link = $category(CatalogSeeder::NEW_ARRIVALS)) === null ? null : $item($link, null, [], 'new'),
            ($link = $category('nu')) === null ? null : $item($link, null, $women),
            $collections === [] ? null : $item(null, 'Bộ sưu tập', $collections),
            ContentSeeder::enabled('vani.lookbook') ? $item(['type' => 'url', 'value' => '/lookbook'], 'Lookbook') : null,
            $page('news', 'Tin tức'),
        ]));

        $support = array_values(array_filter([
            $item(['type' => 'route', 'value' => 'storefront.track'], 'Tra cứu đơn hàng'),
            $page('returns', 'Đổi trả'), $page('size_guide', 'Chọn size'), $page('privacy', 'Bảo mật'),
        ]));
        $about = array_values(array_filter([
            $page('about', 'Giới thiệu'),
            $page('news', 'Tin tức'),
            $item(['type' => 'route', 'value' => 'storefront.brands'], 'Thương hiệu'),
        ]));

        return [
            'header' => $header,
            'mobile' => $this->mobileMenu(),
            // Cột chân trang một cấp: tiêu đề cột là tên vị trí trong theme.
            'footer_1' => $support,
            'footer_2' => $about,
        ];
    }

    /** @return array<string, array{type: string, value: string}> */
    private function contentLinks(): array
    {
        $links = [];
        foreach (app(Extensions::class)->tagged(DemoContent::TAG) as $content) {
            if ($content instanceof DemoContent) {
                $links += $content->links();
            }
        }

        return $links;
    }
}
