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
        $this->translations($this->pluginPath('Resources/lang'), 'vani-cms');
        $this->permissions(['cms.view' => 'vani-cms::texts.cms_service_provider.view_content_pages_posts', 'cms.manage' => 'vani-cms::texts.cms_service_provider.write_publish_delete_content']);

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

        $this->adminMenu('cms', 'vani-cms::texts.cms_service_provider.content', 'admin.plugins.vani-cms.pages.index', 'cms.view', 620, 'content');
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
            : new SlotView('vani-cms::announcement', [
                'announcement' => $announcement,
                // Màu nền/chữ cửa hàng chọn → thuộc tính style; script xoay vòng chỉ khi có > 1 dòng.
                'style' => trim(($announcement['bg'] ? "background-color:{$announcement['bg']};" : '').($announcement['fg'] ? "color:{$announcement['fg']};" : '')),
                'scriptSrc' => count($announcement['lines']) > 1 ? vani_asset(self::ID, 'announcement.js') : '',
            ]));
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
            ...array_map(fn (string $locale): FieldDefinition => FieldDefinition::text(ExtraContent::highlightKey($locale), __('vani-cms::texts.cms_service_provider.highlights_locale', ['locale' => $locale]), help: __('vani-cms::texts.cms_service_provider.one_point_per_line_most').ExtraContent::MAX_HIGHLIGHTS.__('vani-cms::texts.cms_service_provider.shown_below_product_name'), max: 2000), StoreLocale::supported()),
            FieldDefinition::bool('hide_promo_box', __('vani-cms::texts.cms_service_provider.hide_shared_offer_box_product')),
        ];
        $this->adminFormSection('product', 'extras', 'vani-cms::texts.cms_service_provider.extra_content', $fields,
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
            ['key' => 'announcement_enabled', 'label' => 'vani-cms::texts.cms_service_provider.enable_announcement_bar', 'type' => 'bool', 'default' => false,
                'help' => 'vani-cms::texts.cms_service_provider.multiple_lines_rotating_automatically_when'],
            ['key' => 'announcement_bg', 'label' => 'vani-cms::texts.cms_service_provider.announcement_bar_background_colour', 'help' => 'vani-cms::texts.cms_service_provider.format_rrggbb_empty_theme_s', 'rules' => $color],
            ['key' => 'announcement_fg', 'label' => 'vani-cms::texts.cms_service_provider.announcement_bar_text_colour', 'help' => 'vani-cms::texts.cms_service_provider.format_rrggbb_empty_white', 'rules' => $color],
            ['key' => 'announcement_pause_ms', 'label' => 'vani-cms::texts.cms_service_provider.time_each_line_shown_ms', 'type' => 'int', 'default' => 3000, 'rules' => ['min:1000', 'max:15000']],
            ['key' => 'announcement_fade_ms', 'label' => 'vani-cms::texts.cms_service_provider.line_transition_time_ms_0', 'type' => 'int', 'default' => 500, 'rules' => ['min:0', 'max:2000']],
            ['key' => 'announcement_from', 'label' => 'vani-cms::texts.cms_service_provider.show_from_store_time', 'help' => 'vani-cms::texts.cms_service_provider.format_2026_11_20_0800', 'rules' => $time],
            ['key' => 'announcement_to', 'label' => 'vani-cms::texts.cms_service_provider.show_until_store_time', 'help' => 'vani-cms::texts.cms_service_provider.format_2026_11_30_2359', 'rules' => $time],
            ['key' => 'promo_box_enabled', 'label' => 'vani-cms::texts.cms_service_provider.enable_offer_box_product_pages', 'type' => 'bool', 'default' => false, 'help' => 'vani-cms::texts.cms_service_provider.one_shared_content_every_product'],
            ['key' => 'promo_box_position', 'label' => 'vani-cms::texts.cms_service_provider.offer_box_position', 'type' => 'select', 'default' => 'after_add_to_cart', 'options' => ExtraContent::POSITIONS],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $definitions[] = ['key' => "announcement_lines.{$locale}", 'label' => 'vani-cms::texts.cms_service_provider.announcement_lines_locale', 'type' => 'text',
                'help' => 'vani-cms::texts.cms_service_provider.one_announcement_per_line_single'];
            $definitions[] = ['key' => "promo_box_markdown.{$locale}", 'label' => 'vani-cms::texts.cms_service_provider.offer_box_content_locale_markdown', 'type' => 'text'];
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
