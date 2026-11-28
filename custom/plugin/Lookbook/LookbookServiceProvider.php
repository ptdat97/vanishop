<?php

declare(strict_types=1);

namespace Plugin\Lookbook;

use Modules\Catalog\Contracts\ImageSizeProvider;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Storefront\Contracts\SitemapProvider;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Lookbook\Infrastructure\LatestLookbooksBlock;
use Plugin\Lookbook\Infrastructure\LookbookImageSizes;
use Plugin\Lookbook\Infrastructure\LookbookSitemap;
use Plugin\Lookbook\Persistence\Lookbook;
use Plugin\Lookbook\Persistence\LookbookImage;

/**
 * Lookbook (docs/05-plugin/specs/lookbook.md): bộ ảnh thời trang theo mùa/chủ đề — trang danh sách, trang chi tiết,
 * khối trang chủ, sitemap, Storefront API, Admin → Nội dung → Lookbook.
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
        // Dữ liệu hiện trên trang công khai: lưu/xoá → cache trang hết hiệu lực trong lô kế tiếp (ADR-051).
        $this->pageCacheModels([Lookbook::class], fn (Lookbook $book): array => [self::ID.':listing', self::ID.':book:'.$book->id]);
        $this->pageCacheModels([LookbookImage::class], fn (LookbookImage $image): array => [self::ID.':listing', self::ID.':book:'.$image->lookbook_id]);
        $this->translations($this->pluginPath('Resources/lang'), 'vani-lookbook');
        $this->permissions(['lookbook.view' => 'Xem lookbook', 'lookbook.manage' => 'vani-lookbook::texts.lookbook_service_provider.create_edit_delete_lookbooks'], [PermissionRegistry::PRESET_CONTENT]);
        $this->settings([
            ['key' => 'per_page', 'label' => 'vani-lookbook::texts.lookbook_service_provider.lookbooks_per_page_6_48', 'type' => 'int', 'default' => 12, 'rules' => ['min:6', 'max:48']],
        ], [PermissionRegistry::PRESET_CONTENT]);

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-lookbook');
        $this->storefrontPages($this->pluginPath('Http/routes/pages.php'), prefix: ['vi' => 'lookbook', 'en' => 'lookbook'], cacheable: true);
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));
        $this->contribute(StorefrontBlock::TAG, LatestLookbooksBlock::class);
        $this->contribute(SitemapProvider::TAG, LookbookSitemap::class);
        $this->contribute(ImageSizeProvider::TAG, LookbookImageSizes::class);
        $this->adminMenu('lookbook', 'Lookbook', 'admin.plugins.vani-lookbook.index', 'lookbook.view', 630, 'content');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('Lookbook', $this->pluginPath('Resources/js/Pages'));
    }
}
