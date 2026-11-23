<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\ContextualStorefrontBlock;
use Modules\Storefront\Contracts\Data\BlockContext;

final class ListingProductGridBlock implements ContextualStorefrontBlock
{
    public function __construct(private readonly ProductGridBlock $grid) {}

    public function type(): string
    {
        return 'listing_product_grid';
    }

    public function label(): string
    {
        return __('storefront::admin.builder.listing_grid');
    }

    public function slots(): array
    {
        return ['vani.storefront.plp.before_listing', 'vani.storefront.plp.after_listing', 'vani.storefront.plp.empty'];
    }

    public function fields(): array
    {
        return array_values(array_filter($this->grid->fields(), fn (FieldDefinition $field): bool => in_array($field->key, ['title', 'limit'], true)));
    }

    public function resolve(array $config, string $locale): array
    {
        return [];
    }

    public function resolveInContext(array $config, string $locale, BlockContext $context): ?array
    {
        if ($context->listingSlug === null || ! in_array($context->listingType, ['brand', 'category', 'collection'], true)) {
            return null;
        }

        $data = $this->grid->resolve([
            'title' => $config['title'] ?? null,
            'limit' => $config['limit'] ?? null,
            'source' => $context->listingType,
            'slug' => $context->listingSlug,
        ], $locale);

        return $data['products'] === [] ? null : $data;
    }

    public function view(): string
    {
        return $this->grid->view();
    }
}
