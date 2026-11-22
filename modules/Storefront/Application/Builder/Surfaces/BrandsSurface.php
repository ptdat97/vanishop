<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder\Surfaces;

use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;

final class BrandsSurface implements BuilderSurface
{
    public function key(): string
    {
        return 'brands';
    }

    public function label(): string
    {
        return __('storefront::admin.builder.surfaces.brands');
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.brands.before_list', __('storefront::admin.builder.zones.brands_before_list')),
            BuilderZone::slot('vani.storefront.brands.after_list', __('storefront::admin.builder.zones.brands_after_list')),
        ];
    }
}
