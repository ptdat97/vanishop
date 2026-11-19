<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Shared\Support\LocalizedUrls;

final class CollectionLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return __('storefront::texts.collection_link_type.collections');
    }

    protected function kind(): string
    {
        return 'collection';
    }

    protected function url(string $slug, string $locale): string
    {
        return LocalizedUrls::route('storefront.search', ['collection' => $slug], $locale);
    }
}
