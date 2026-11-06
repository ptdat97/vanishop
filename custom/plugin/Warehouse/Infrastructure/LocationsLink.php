<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Infrastructure;

use Illuminate\Support\Facades\Gate;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Inventory\Contracts\StockPageLink;

/**
 * Nút "Kho & cửa hàng" trên trang Tồn kho; plugin quản lý danh sách kho nên Core ẩn form "Kho chính".
 */
final class LocationsLink implements StockPageLink
{
    public function label(): string
    {
        return 'Kho & cửa hàng';
    }

    public function url(): ?string
    {
        return Gate::allows('warehouse.locations.manage', [ScopeRef::owner()]) ? route('admin.plugins.vani-warehouse.locations.index') : null;
    }

    public function managesLocations(): bool
    {
        return true;
    }
}
