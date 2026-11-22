<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

final class FeaturedCollectionBlock implements StorefrontBlock
{
    public function __construct(
        private readonly CatalogLookup $catalog,
        private readonly MediaDirectory $media,
        private readonly Products $products,
    ) {}

    public function type(): string
    {
        return 'extras_featured_collection';
    }

    public function label(): string
    {
        return __('vani-storefront-extras::storefront.featured_collection');
    }

    public function fields(): array
    {
        $choices = array_column($this->catalog->search('collection', '', app()->getLocale(), 200), 'name', 'id');

        return [
            FieldDefinition::select('collection', __('vani-storefront-extras::storefront.collection'), $choices, required: true),
            FieldDefinition::text('description', __('vani-storefront-extras::storefront.collection_description'), max: 300),
            FieldDefinition::media('image', __('vani-storefront-extras::storefront.collection_image')),
            FieldDefinition::string('image_alt', __('vani-storefront-extras::storefront.collection_image_alt'), max: 160),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $id = (int) ($config['collection'] ?? 0);
        $collection = $id > 0 ? ($this->catalog->visible('collection', [$id], $locale)[$id] ?? null) : null;
        $imageId = (int) ($config['image'] ?? 0);
        $media = $collection !== null && $imageId > 0 ? ($this->media->find([$imageId])[$imageId] ?? null) : null;
        $image = $media === null ? null : ['url' => $media->urlFor(800), 'srcset' => $media->srcset(), 'width' => $media->width, 'height' => $media->height];

        return [
            'title' => $collection['name'] ?? '',
            'url' => $collection === null ? null : LocalizedUrls::route('storefront.search', ['collection' => $collection['slug']], $locale),
            'description' => trim((string) ($config['description'] ?? '')),
            'image' => $image,
            'alt' => trim((string) ($config['image_alt'] ?? '')),
            'products' => $collection === null ? [] : $this->products->from('collection', $collection['slug'], 2, $locale),
        ];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.featured-collection';
    }
}
