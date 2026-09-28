<?php

declare(strict_types=1);

namespace Modules\Brand\Application;

use Modules\Brand\Contracts\BrandDirectory;
use Modules\Brand\Contracts\Data\BrandData;
use Modules\Brand\Persistence\Models\Brand;

final class EloquentBrandDirectory implements BrandDirectory
{
    public function find(int $brandId): ?BrandData
    {
        $brand = Brand::query()->find($brandId);

        return $brand === null ? null : new BrandData($brand->id, $brand->legal_entity_id, $brand->code, $brand->name, $brand->slug);
    }

    public function list(?array $brandIds): array
    {
        return Brand::query()
            ->when($brandIds !== null, fn ($query) => $query->whereIn('id', $brandIds))
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand): BrandData => new BrandData($brand->id, $brand->legal_entity_id, $brand->code, $brand->name, $brand->slug))
            ->all();
    }

    public function idsOfLegalEntity(int $legalEntityId): array
    {
        return Brand::query()->where('legal_entity_id', $legalEntityId)->orderBy('id')->pluck('id')->all();
    }
}
