<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

final class CollectionLinkType extends CatalogLinkType
{
    public function label(): string
    {
        return 'Bộ sưu tập';
    }

    protected function kind(): string
    {
        return 'collection';
    }

    protected function url(string $slug): string
    {
        return route('storefront.search', ['collection' => $slug]);
    }
}
