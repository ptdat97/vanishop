<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Shared\Support\LocalizedUrls;

final class CategoryLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return 'Danh mục';
    }

    protected function kind(): string
    {
        return 'category';
    }

    protected function url(string $slug, string $locale): string
    {
        return LocalizedUrls::route('storefront.category', $slug, $locale);
    }
}
