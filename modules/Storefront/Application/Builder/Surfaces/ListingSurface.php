<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder\Surfaces;

use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;

final class ListingSurface implements BuilderSurface
{
    public function key(): string
    {
        return 'listing';
    }

    public function label(): string
    {
        return __('storefront::admin.builder.surfaces.listing');
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.plp.header', __('storefront::admin.builder.zones.plp_header')),
            BuilderZone::slot('vani.storefront.plp.before_listing', __('storefront::admin.builder.zones.plp_before_listing')),
            BuilderZone::slot('vani.storefront.plp.after_listing', __('storefront::admin.builder.zones.plp_after_listing')),
            BuilderZone::slot('vani.storefront.plp.empty', __('storefront::admin.builder.zones.plp_empty')),
        ];
    }
}
