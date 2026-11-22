<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder\Surfaces;

use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;

final class CartSurface implements BuilderSurface
{
    public function key(): string
    {
        return 'cart';
    }

    public function label(): string
    {
        return __('storefront::admin.builder.surfaces.cart');
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.cart.empty', __('storefront::admin.builder.zones.cart_empty')),
        ];
    }
}
