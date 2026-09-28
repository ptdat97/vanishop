<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Http\Controllers\BrandWorkspaceHome;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;

final class ShipmentController
{
    /** Trạng thái nhân viên cập nhật tay (các trạng thái đặt vận đơn đi qua thao tác "nhập mã"). */
    private const MANUAL_STATUSES = [ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, ShipmentStatus::FailedAttempt, ShipmentStatus::Delivered, ShipmentStatus::Returning, ShipmentStatus::Returned];

    public function home(BrandWorkspaceHome $home): Response|RedirectResponse
    {
        Gate::authorize('fulfillment.view');

        return $home->respond('admin.fulfillment.shipments.index', 'Giao hàng', 'Chọn brand để xử lý vận đơn.');
    }

    public function index(Brand $brand, Request $request, OrderReader $orders, CarrierRegistry $registry): Response
    {
        Gate::authorize('fulfillment.view', [ScopeRef::brand($brand->id)]);
        $status = (string) $request->query('status', '');
        $number = strtoupper(trim((string) $request->query('order', '')));
        $order = $number === '' ? null : $orders->findByNumber($number);

        $shipments = Shipment::query()->with('lines')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($number !== '', fn ($query) => $query->where('order_id', $order->id ?? 0))
            ->orderByDesc('id')->limit(100)->get();

        return Inertia::render('Fulfillment::Shipments/Index', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'baseUrl' => route('admin.fulfillment.shipments.index'),
            'filters' => ['status' => $status, 'order' => $number],
            'statuses' => array_column(ShipmentStatus::cases(), 'value'),
            'order' => $order === null ? null : [
                'id' => $order->id, 'number' => $order->number, 'status' => $order->status->value,
                'can_create' => in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing], true)
                    && ! Shipment::query()->where('order_id', $order->id)->where('status', '!=', ShipmentStatus::Cancelled)->exists(),
            ],
            'shipments' => $shipments->map(fn (Shipment $shipment): array => [
                'id' => $shipment->id,
                'order_number' => $orders->find($shipment->order_id)?->number,
                'carrier' => $registry->carrier($shipment->carrier_code)?->label() ?? $shipment->carrier_code,
                'manual' => ! ($registry->carrier($shipment->carrier_code)?->capabilities()->autoBooking ?? false),
                'service_code' => $shipment->service_code,
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status->value,
                'items' => $shipment->lines->sum('quantity'),
                'cod_amount' => $shipment->cod_amount,
                'last_error' => $shipment->last_error,
                'next' => array_values(array_map(fn (ShipmentStatus $to): string => $to->value, array_filter(self::MANUAL_STATUSES, fn (ShipmentStatus $to): bool => $shipment->status->rank() >= 1 && $shipment->status->canMoveTo($to)))),
                'can_book' => in_array($shipment->status, [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed], true),
                'can_cancel' => $shipment->status->canMoveTo(ShipmentStatus::Cancelled),
            ])->all(),
            'canManage' => Gate::allows('fulfillment.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function store(Brand $brand, Request $request, OrderReader $orders, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage', [ScopeRef::brand($brand->id)]);
        $data = $request->validate(['order_id' => ['required', 'integer']]);
        abort_if($orders->find((int) $data['order_id']) === null, 404);
        $fulfillment->createForOrder((int) $data['order_id']);

        return back()->with('success', __('fulfillment::messages.created'));
    }

    public function book(Brand $brand, Shipment $shipment, Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage', [ScopeRef::brand($brand->id)]);
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'], 'service_code' => ['nullable', 'string', 'max:64']]);
        $fulfillment->book($shipment->id, $data['tracking_number'], $data['service_code'] ?? null);

        return back()->with('success', __('fulfillment::messages.booked'));
    }

    public function status(Brand $brand, Shipment $shipment, Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage', [ScopeRef::brand($brand->id)]);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn (ShipmentStatus $status): string => $status->value, self::MANUAL_STATUSES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $fulfillment->updateStatus($shipment->id, ShipmentStatus::from($data['status']), 'staff:'.now()->format('Uu').':'.$data['status'], 'staff', $data['note'] ?? null, strict: true);

        return back()->with('success', __('fulfillment::messages.updated'));
    }

    public function cancel(Brand $brand, Shipment $shipment, Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage', [ScopeRef::brand($brand->id)]);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $fulfillment->cancel($shipment->id, $data['reason']);

        return back()->with('success', __('fulfillment::messages.cancelled'));
    }
}
