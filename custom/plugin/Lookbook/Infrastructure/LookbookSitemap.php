<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Infrastructure;

use Modules\Storefront\Contracts\SitemapProvider;
use Plugin\Lookbook\Persistence\Lookbook;

/**
 * Trang danh sách + lookbook đang bật (tắt thì không có, tiêu chí §8.1).
 */
final class LookbookSitemap implements SitemapProvider
{
    public function urls(int $limit): array
    {
        if ($limit < 1 || ! Lookbook::query()->visible()->exists()) {
            return [];
        }

        return [
            vani_route('storefront.p.vani-lookbook.index'),
            ...Lookbook::query()->visible()->orderBy('position')->limit($limit - 1)->pluck('slug')->map(fn (string $slug): string => vani_route('storefront.p.vani-lookbook.show', $slug))->all(),
        ];
    }
}
