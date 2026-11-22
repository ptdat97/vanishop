<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder\Surfaces;

use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;

final class HomeSurface implements BuilderSurface
{
    public function key(): string
    {
        return 'home';
    }

    public function label(): string
    {
        return __('storefront::admin.builder.surfaces.home');
    }

    public function zones(): array
    {
        return [
            BuilderZone::page('main', __('storefront::admin.builder.zones.home_main')),
        ];
    }
}
