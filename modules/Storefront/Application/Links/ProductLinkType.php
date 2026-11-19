<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Shared\Support\LocalizedUrls;

final class ProductLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return __('storefront::texts.product_link_type.products');
    }

    protected function kind(): string
    {
        return 'product';
    }

    protected function url(string $slug, string $locale): string
    {
        return LocalizedUrls::route('storefront.product', $slug, $locale);
    }
}
