<?php

declare(strict_types=1);

namespace Plugin\Lookbook;

use Modules\Catalog\Contracts\ImageSizeProvider;
use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\SitemapProvider;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Lookbook\Infrastructure\LatestLookbooksBlock;
use Plugin\Lookbook\Infrastructure\LookbookImageSizes;
use Plugin\Lookbook\Infrastructure\LookbookSitemap;
use Plugin\Lookbook\Persistence\Lookbook;

/**
 * Lookbook (docs/05-plugin/specs/lookbook.md): bộ ảnh thời trang theo mùa/chủ đề — trang danh sách, trang chi tiết,
 * khối trang chủ, link header, sitemap, Storefront API, Admin → Nội dung → Lookbook.
 */
final class LookbookServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.lookbook';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom($this->pluginPath('Resources/lang'));
        $this->permissions(['lookbook.view' => 'Xem lookbook', 'lookbook.manage' => 'Tạo, sửa, xoá lookbook']);
        $this->settings([
            ['key' => 'show_in_header', 'label' => 'Link "Lookbook" ở đầu trang', 'type' => 'bool', 'default' => true, 'help' => 'Chỉ hiện khi có ít nhất một lookbook đang bật.'],
            ['key' => 'per_page', 'label' => 'Số lookbook mỗi trang (6–48)', 'type' => 'int', 'default' => 12, 'rules' => ['min:6', 'max:48']],
        ]);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-lookbook');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'), prefix: ['vi' => 'lookbook', 'en' => 'lookbook'], cacheable: true);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));
        $this->contribute(StorefrontBlock::TAG, LatestLookbooksBlock::class);
        $this->contribute(SitemapProvider::TAG, LookbookSitemap::class);
        $this->contribute(ImageSizeProvider::TAG, LookbookImageSizes::class);
        $this->onSlot('vani.storefront.header.nav', fn (): ?SlotView => (bool) vani_setting(self::ID, 'show_in_header', true) && Lookbook::query()->visible()->exists()
            ? new SlotView('vani-lookbook::header-link')
            : null);

        $this->adminMenu('lookbook', 'Lookbook', 'admin.plugins.vani-lookbook.index', 'lookbook.view', 630, 'content');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('Lookbook', $this->pluginPath('Resources/js/Pages'));
    }
}
