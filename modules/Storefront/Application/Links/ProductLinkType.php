<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

final class ProductLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return 'Sản phẩm';
    }

    protected function kind(): string
    {
        return 'product';
    }

    protected function url(string $slug): string
    {
        return route('storefront.product', $slug);
    }
}
