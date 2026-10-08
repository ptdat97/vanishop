<?php

declare(strict_types=1);

namespace Plugin\Cms;

use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\SitemapProvider;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Cms\Infrastructure\CmsSitemap;
use Plugin\Cms\Infrastructure\LatestPostsBlock;
use Plugin\Cms\Infrastructure\Markdown;
use Plugin\Cms\Infrastructure\Navigation;

/**
 * Plugin nội dung: trang (/trang/{slug}: giới thiệu, chính sách…) và tin tức (/tin-tuc), soạn bằng Markdown trong
 * Admin → Nội dung, hẹn giờ đăng, xem trước bản nháp, SEO; link ở header/footer, khối "Bài viết mới" cho trang chủ,
 * sitemap, Storefront API cho headless. Dữ liệu ở bảng plg_cms_* (ADR-027).
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
        $this->permissions(['cms.view' => 'Xem nội dung (trang, bài viết)', 'cms.manage' => 'Soạn, đăng, xoá nội dung']);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-cms');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'), prefix: 'trang', cacheable: true);
        $this->storefrontPages($this->pluginPath('Http/routes/blog.php'), prefix: 'tin-tuc', cacheable: true);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));

        $this->contribute(StorefrontBlock::TAG, LatestPostsBlock::class);
        $this->contribute(SitemapProvider::TAG, CmsSitemap::class);
        $this->onSlot('vani.storefront.header.nav', fn (): ?SlotView => $this->links('header'));
        $this->onSlot('vani.storefront.footer.columns', fn (): ?SlotView => $this->links('footer'));

        $this->adminMenu('cms', 'Nội dung', 'admin.plugins.vani-cms.pages.index', 'cms.view', 620, 'content');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('Cms', $this->pluginPath('Resources/js/Pages'));
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
