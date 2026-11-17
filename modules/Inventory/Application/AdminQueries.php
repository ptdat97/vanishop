<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockReceipt;

/**
 * Truy vấn đọc cho màn hình Admin của Inventory — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    public function location(int $id): Location
    {
        return Location::query()->findOrFail($id);
    }

    /**
     * Phiếu nhập (A6), mới nhất trước; tìm theo số phiếu / nhà cung cấp.
     *
     * @return LengthAwarePaginator<int, StockReceipt>
     */
    public function receipts(string $search, ?string $status, int $perPage): LengthAwarePaginator
    {
        return StockReceipt::query()->with('location:id,code,name')->withCount('lines')->withSum('lines', 'quantity')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match->where('number', 'like', '%'.addcslashes($search, '%_\\').'%')->orWhere('supplier', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Kho VaniShop quản lý tồn vật lý (nhập hàng được).
     *
     * @return list<Location>
     */
    public function managedLocations(): array
    {
        return array_values(array_filter(Location::query()->orderByDesc('priority')->orderBy('code')->get()->all(), fn (Location $location): bool => $location->managesOnHand()));
    }
}
