<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Persistence\Models\Variant;

final class EloquentVariantDirectory implements VariantDirectory
{
    public function ofStyleCode(int $brandId, string $styleCode): array
    {
        return Variant::query()
            ->with(['style.translations', 'styleColor.color', 'size'])
            ->where('brand_id', $brandId)
            ->whereHas('style', fn ($query) => $query->where('style_code', $styleCode))
            ->get()
            ->sortBy(fn (Variant $variant): string => sprintf('%05d-%05d', $variant->styleColor->position, $variant->size->sort_order))
            ->map(fn (Variant $variant): VariantData => $this->toData($variant))
            ->values()
            ->all();
    }

    public function find(array $variantIds): array
    {
        return Variant::query()
            ->with(['style.translations', 'styleColor.color', 'size'])
            ->whereIn('id', $variantIds)
            ->get()
            ->mapWithKeys(fn (Variant $variant): array => [$variant->id => $this->toData($variant)])
            ->all();
    }

    public function findBySkus(array $skus): array
    {
        if ($skus === []) {
            return [];
        }

        return Variant::query()
            ->with(['style.translations', 'styleColor.color', 'size'])
            ->whereIn('sku', array_values(array_unique($skus)))
            ->get()
            ->mapWithKeys(fn (Variant $variant): array => [$variant->sku => $this->toData($variant)])
            ->all();
    }

    private function toData(Variant $variant): VariantData
    {
        return new VariantData(
            id: $variant->id,
            brandId: $variant->brand_id,
            styleId: $variant->style_id,
            styleCode: $variant->style->style_code,
            styleName: (string) $variant->style->translate('name'),
            sku: $variant->sku,
            colorCode: $variant->styleColor->color->code,
            sizeCode: $variant->size->code,
            status: $variant->status->value,
        );
    }
}
