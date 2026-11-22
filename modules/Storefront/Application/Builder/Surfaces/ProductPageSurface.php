<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder\Surfaces;

use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;

final class ProductPageSurface implements BuilderSurface
{
    public function key(): string
    {
        return 'product';
    }

    public function label(): string
    {
        return __('storefront::admin.builder.surfaces.product');
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.pdp.after_details', __('storefront::admin.builder.zones.pdp_after_details'), types: ['rich_text'], max: 3),
            BuilderZone::slot('vani.storefront.pdp.after_related_products', __('storefront::admin.builder.zones.pdp_after_related_products')),
        ];
    }
}
