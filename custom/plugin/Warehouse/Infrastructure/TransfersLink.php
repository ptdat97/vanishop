<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Infrastructure;

use Illuminate\Support\Facades\Gate;
use Modules\Inventory\Contracts\StockPageLink;

/**
 * Nút "Chuyển kho" trên trang Tồn kho.
 */
final class TransfersLink implements StockPageLink
{
    public function label(): string
    {
        return 'Chuyển kho';
    }

    public function url(): ?string
    {
        return Gate::allows('inventory.view') ? route('admin.plugins.vani-warehouse.transfers.index') : null;
    }

    public function managesLocations(): bool
    {
        return false;
    }
}
