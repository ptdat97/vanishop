<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

final class BrandLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return 'Thương hiệu';
    }

    protected function kind(): string
    {
        return 'brand';
    }

    protected function url(string $slug): string
    {
        return route('storefront.brand', $slug);
    }
}
