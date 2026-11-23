<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Shared\Http\ListSort;

/**
 * Truy vấn đọc cho màn hình Admin và webhook của Fulfillment — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * Danh sách vận đơn: lọc trạng thái, theo đơn (`$orderId` null = mọi đơn, 0 = không đơn nào), id do bộ lọc plugin trả.
     *
     * @param  list<int>|null  $ids
     * @return LengthAwarePaginator<int, Shipment>
     */
    public function shipments(string $status, ?int $orderId, ?array $ids, int $perPage, ?ListSort $sort = null): LengthAwarePaginator
    {
        return Shipment::query()->with('lines')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($orderId !== null, fn ($query) => $query->where('order_id', $orderId))
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($sort !== null, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('id'))->paginate($perPage)->withQueryString();
    }

    public function hasActiveShipments(int $orderId): bool
    {
        return Shipment::query()->where('order_id', $orderId)->where('status', '!=', ShipmentStatus::Cancelled)->exists();
    }

    /**
     * @param  list<int>  $orderIds
     * @return Collection<int, Shipment>
     */
    public function activeShipmentsOf(array $orderIds): Collection
    {
        return Shipment::query()->whereIn('order_id', $orderIds)->where('status', '!=', ShipmentStatus::Cancelled)->get();
    }

    /**
     * @param  list<int>  $orderIds
     * @return Collection<int, Shipment>
     */
    public function shipmentsOf(array $orderIds, ShipmentStatus $status): Collection
    {
        return Shipment::query()->whereIn('order_id', $orderIds)->where('status', $status)->get();
    }

    /**
     * @param  list<int>  $shipmentIds
     * @return Collection<int, Shipment>
     */
    public function readyShipments(array $shipmentIds): Collection
    {
        return Shipment::query()->whereKey($shipmentIds)->where('status', ShipmentStatus::Created)
            ->whereNotNull('tracking_number')->where('tracking_number', '!=', '')->get();
    }

    public function byTracking(string $carrier, string $trackingNumber): ?Shipment
    {
        return Shipment::query()->where('carrier_code', $carrier)->where('tracking_number', $trackingNumber)->first();
    }
}
