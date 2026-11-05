<?php

declare(strict_types=1);

namespace Plugin\Cms;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\PluginServiceProvider;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\LinkType;
use Modules\Storefront\Contracts\SitemapProvider;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\Cms\Infrastructure\ArticleCategoryLinkType;
use Plugin\Cms\Infrastructure\ArticleLinkType;
use Plugin\Cms\Infrastructure\CmsSitemap;
use Plugin\Cms\Infrastructure\ExtraContent;
use Plugin\Cms\Infrastructure\LatestPostsBlock;
use Plugin\Cms\Infrastructure\Markdown;
use Plugin\Cms\Infrastructure\Navigation;
use Plugin\Cms\Infrastructure\PageLinkType;
use Plugin\Cms\Infrastructure\ProductExtrasEnricher;

/**
 * Plugin nội dung: trang (/trang/{slug}: giới thiệu, chính sách…) và tin tức (/tin-tuc), soạn bằng Markdown trong
 * Admin → Nội dung, hẹn giờ đăng, xem trước bản nháp, SEO; link ở header/footer, khối "Bài viết mới" cho trang chủ,
 * sitemap, Storefront API cho headless. Dữ liệu ở bảng plg_cms_* (ADR-027).
 *
 * Nội dung thêm (1.5.0, docs/05-plugin/specs/store-content.md): thanh thông báo nhiều dòng, hộp ưu đãi và điểm nổi bật ở
 * trang sản phẩm, banner đầu trang danh mục.
 */
final class CmsServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.cms';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->app->singleton(Markdown::class);
    }

    public function boot(): void
    {
        // Chuỗi giao diện storefront của plugin theo ngôn ngữ (khoá = câu tiếng Việt, ADR-038); theme ghi đè được.
        $this->loadJsonTranslationsFrom($this->pluginPath('Resources/lang'));
        $this->permissions(['cms.view' => 'Xem nội dung (trang, bài viết)', 'cms.manage' => 'Soạn, đăng, xoá nội dung']);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-cms');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'), prefix: ['vi' => 'trang', 'en' => 'pages'], cacheable: true);
        $this->storefrontPages($this->pluginPath('Http/routes/blog.php'), prefix: ['vi' => 'tin-tuc', 'en' => 'news'], cacheable: true);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));

        $this->contribute(StorefrontBlock::TAG, LatestPostsBlock::class);
        $this->contribute(SitemapProvider::TAG, CmsSitemap::class);
        $this->contribute(LinkType::TAG, PageLinkType::class);
        $this->contribute(LinkType::TAG, ArticleLinkType::class);
        $this->contribute(LinkType::TAG, ArticleCategoryLinkType::class);
        $this->onSlot('vani.storefront.header.nav', fn (): ?SlotView => $this->links('header'));
        $this->onSlot('vani.storefront.footer.columns', fn (): ?SlotView => $this->links('footer'));
        $this->extraContent();

        $this->adminMenu('cms', 'Nội dung', 'admin.plugins.vani-cms.pages.index', 'cms.view', 620, 'content');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('Cms', $this->pluginPath('Resources/js/Pages'));
    }

    private function extraContent(): void
    {
        $content = fn (): ExtraContent => $this->app->make(ExtraContent::class);
        $this->settings($this->extraContentSettings());
        $this->contribute(StorefrontEnricher::TAG, ProductExtrasEnricher::class);

        $this->onSlot('vani.storefront.layout.body_start', fn (): ?SlotView => ($announcement = $content()->announcement(app()->getLocale())) === null
            ? null
            : new SlotView('vani-cms::announcement', ['announcement' => $announcement]));
        $this->onSlot('vani.storefront.pdp.after_title', fn (array $product): ?SlotView => ($product['extensions'][self::ID]['highlights'] ?? []) === []
            ? null
            : new SlotView('vani-cms::highlights', ['highlights' => $product['extensions'][self::ID]['highlights']]));
        foreach (array_keys(ExtraContent::POSITIONS) as $position) {
            $this->onSlot("vani.storefront.pdp.{$position}", fn (array $product): ?SlotView => $content()->promoPosition() === $position && ($html = $product['extensions'][self::ID]['promo_box'] ?? null) !== null
                ? new SlotView('vani-cms::promo-box', ['html' => $html])
                : null);
        }
        $this->onSlot('vani.storefront.plp.before_listing', fn (array $context): ?SlotView => ($context['type'] ?? null) !== 'category' || ! isset($context['category']['id'])
            || ($banner = $content()->banner((int) $context['category']['id'], app()->getLocale())) === null
            ? null
            : new SlotView('vani-cms::category-banner', ['banner' => $banner]));

        // Form sản phẩm: điểm nổi bật theo ngôn ngữ (mỗi dòng một ý) + ẩn hộp ưu đãi.
        $fields = fn (): array => [
            ...array_map(fn (string $locale): FieldDefinition => FieldDefinition::text(ExtraContent::highlightKey($locale), "Điểm nổi bật ({$locale})", help: 'Mỗi dòng một ý, tối đa '.ExtraContent::MAX_HIGHLIGHTS.'; hiện dưới tên sản phẩm.', max: 2000), StoreLocale::supported()),
            FieldDefinition::bool('hide_promo_box', 'Ẩn hộp ưu đãi dùng chung ở sản phẩm này'),
        ];
        $this->adminFormSection('product', 'extras', 'Nội dung thêm', $fields,
            load: fn (int $id): array => $content()->productForm($id),
            save: fn (int $id, array $values) => $content()->saveProduct($id, $values),
            order: 400,
        );
    }

    /**
     * Cấu hình (đặc tả §5). Thanh thông báo của plugin thay cho dòng thông báo đơn của theme (Giao diện → Đầu & chân trang).
     *
     * @return list<array<string, mixed>>
     */
    private function extraContentSettings(): array
    {
        $time = ['regex:/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/'];
        $color = ['regex:/^#[0-9a-fA-F]{6}$/'];
        $definitions = [
            ['key' => 'announcement_enabled', 'label' => 'Bật thanh thông báo đầu trang', 'type' => 'bool', 'default' => false,
                'help' => 'Nhiều dòng, tự xoay vòng. Khi bật, để trống "Dòng thông báo" ở Giao diện → Đầu & chân trang để không hiện hai thanh.'],
            ['key' => 'announcement_bg', 'label' => 'Màu nền thanh thông báo', 'help' => 'Dạng #rrggbb; trống = màu chính của giao diện.', 'rules' => $color],
            ['key' => 'announcement_fg', 'label' => 'Màu chữ thanh thông báo', 'help' => 'Dạng #rrggbb; trống = trắng.', 'rules' => $color],
            ['key' => 'announcement_pause_ms', 'label' => 'Thời gian hiện mỗi dòng (ms, 1000–15000)', 'type' => 'int', 'default' => 3000, 'rules' => ['min:1000', 'max:15000']],
            ['key' => 'announcement_fade_ms', 'label' => 'Thời gian chuyển dòng (ms, 0–2000)', 'type' => 'int', 'default' => 500, 'rules' => ['min:0', 'max:2000']],
            ['key' => 'announcement_from', 'label' => 'Hiện từ (giờ cửa hàng)', 'help' => 'Dạng 2026-11-20 08:00; trống = ngay.', 'rules' => $time],
            ['key' => 'announcement_to', 'label' => 'Hiện đến (giờ cửa hàng)', 'help' => 'Dạng 2026-11-30 23:59; trống = không hết hạn.', 'rules' => $time],
            ['key' => 'promo_box_enabled', 'label' => 'Bật hộp ưu đãi ở trang sản phẩm', 'type' => 'bool', 'default' => false, 'help' => 'Một nội dung chung cho mọi sản phẩm; ẩn riêng từng sản phẩm ở form sản phẩm → Nội dung thêm.'],
            ['key' => 'promo_box_position', 'label' => 'Vị trí hộp ưu đãi', 'type' => 'select', 'default' => 'after_add_to_cart', 'options' => ExtraContent::POSITIONS],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $definitions[] = ['key' => "announcement_lines.{$locale}", 'label' => "Các dòng thông báo ({$locale})", 'type' => 'text',
                'help' => 'Mỗi dòng một thông báo; Markdown một dòng: **đậm**, [link](/duong-dan). Trống = theo ngôn ngữ mặc định.'];
            $definitions[] = ['key' => "promo_box_markdown.{$locale}", 'label' => "Nội dung hộp ưu đãi ({$locale}, Markdown)", 'type' => 'text'];
        }

        return $definitions;
    }

    /**
     * @param  'header'|'footer'  $area
     */
    private function links(string $area): ?SlotView
    {
        $links = $this->app->make(Navigation::class)->links()[$area];

        return $links === [] ? null : new SlotView("vani-cms::{$area}-links", ['links' => $links]);
    }
}
