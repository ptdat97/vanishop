<?php

declare(strict_types=1);

namespace Plugin\CatalogPages;

use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\BuilderSurface;
use Plugin\CatalogPages\Infrastructure\CatalogPageSurface;

/**
 * Nội dung Builder riêng cho từng danh mục, thương hiệu, bộ sưu tập (ScopedBuilderSurface). Core lưu block theo phạm vi
 * (`category-12`…), dùng chung trình biên tập, xem trước, ghi nhận ảnh và render; vùng có nội dung riêng thay nội dung
 * chung (Admin → Giao diện → Nội dung trang → Danh sách sản phẩm). Nút "Nội dung trang" ở trang sửa của từng đối tượng.
 */
final class CatalogPagesServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.catalog-pages';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-catalog-pages');
        $this->contribute(BuilderSurface::TAG, CatalogPageSurface::class);

        foreach (CatalogPageSurface::KINDS as $kind) {
            $this->adminLink($kind, 'content', 'vani-catalog-pages::texts.edit_content',
                fn (int $id): string => route('admin.storefront.builder.scope.edit', [CatalogPageSurface::KEY, CatalogPageSurface::scope($kind, $id)]),
                permission: 'storefront.manage',
            );
        }
    }
}
