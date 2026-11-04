<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

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

    protected function url(string $slug): string
    {
        return route('storefront.category', $slug);
    }
}
