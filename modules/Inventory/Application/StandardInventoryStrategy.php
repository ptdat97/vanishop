<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Inventory\Contracts\InventoryStrategy;

final class StandardInventoryStrategy implements InventoryStrategy
{
    public function code(): string
    {
        return 'standard';
    }

    public function adjust(array $standardAts): array
    {
        return $standardAts;
    }
}
