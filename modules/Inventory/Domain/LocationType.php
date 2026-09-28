<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

enum LocationType: string
{
    case Warehouse = 'warehouse';
    case Store = 'store';
    case Virtual = 'virtual';
}
