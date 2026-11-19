<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Shared\Support\LocalizedUrls;

final class BrandLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return __('storefront::texts.brand_link_type.brand');
    }

    protected function kind(): string
    {
        return 'brand';
    }

    protected function url(string $slug, string $locale): string
    {
        return LocalizedUrls::route('storefront.brand', $slug, $locale);
    }
}
