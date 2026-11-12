<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleAttributeValue;

final class ProductDocumentBuilder
{
    public function build(Style $style): ProductDocument
    {
        $style->loadMissing(['translations', 'categories', 'collections:id', 'optionValues.value', 'attributeValues.attribute', 'variants.optionValues.type']);

        $categoryIds = $style->categories
            ->flatMap(fn (Category $category): array => $category->categoryPath()->ids)
            ->unique()->sort()->values()->all();

        return new ProductDocument(
            id: $style->id,
            brandId: $style->brand_id,
            styleCode: $style->style_code,
            slug: $style->slug,
            status: $style->status->value,
            publishedFrom: $style->published_from?->getTimestamp(),
            publishedTo: $style->published_to?->getTimestamp(),
            names: $style->translations->pluck('name', 'locale')->map(fn ($name): string => (string) $name)->all(),
            searchText: $style->search_text,
            categoryIds: $categoryIds,
            collectionIds: $style->collections->pluck('id')->sort()->values()->all(),
            colorFamilies: $style->optionValues->map(fn ($row): ?string => $row->value->family)->filter()->unique()->sort()->values()->all(),
            attributeValueIds: $style->attributeValues
                ->filter(fn (StyleAttributeValue $row): bool => $row->attribute_value_id !== null
                    && $row->attribute->kind === AttributeKind::Spec && $row->attribute->is_filterable)
                ->pluck('attribute_value_id')->unique()->sort()->values()->all(),
            createdAt: (int) $style->created_at?->getTimestamp(),
            sizeCodes: $style->variants->where('status', VariantStatus::Active)->map(fn ($variant): ?string => $variant->valueOf(OptionType::SIZE)?->code)->filter()->unique()->sort()->values()->all(),
            listPriceMin: $style->list_price_min,
        );
    }
}
