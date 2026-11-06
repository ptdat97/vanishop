<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Domain\BusinessRuleViolation;

final class ShipmentController
{
    /** Trạng thái nhân viên cập nhật tay (các trạng thái đặt vận đơn đi qua thao tác "nhập mã"). */
    /**
     * Trạng thái nhân viên ghi tay (0.3.96: rút gọn — đã bàn giao, giao không thành công, đã giao, đang hoàn, đã hoàn về).
     * "Đang vận chuyển"/"Đang giao" chỉ đến từ hãng có kết nối (webhook); route vẫn nhận để không vỡ thao tác cũ.
     */
    private const MANUAL_STATUSES = [ShipmentStatus::PickedUp, ShipmentStatus::FailedAttempt, ShipmentStatus::Delivered, ShipmentStatus::Returning, ShipmentStatus::Returned];

    private const ACCEPTED_STATUSES = [...self::MANUAL_STATUSES, ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery];

    public function home(): RedirectResponse
    {
        Gate::authorize('fulfillment.view');

        return redirect()->route('admin.fulfillment.shipments.index');
    }

    public function index(Request $request, OrderReader $orders, CarrierRegistry $registry): Response
    {
        Gate::authorize('fulfillment.view');
        $status = (string) $request->query('status', '');
        $number = strtoupper(trim((string) $request->query('order', '')));
        $order = $number === '' ? null : $orders->findByNumber($number);

        $shipments = Shipment::query()->with('lines')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($number !== '', fn ($query) => $query->where('order_id', $order->id ?? 0))
            ->orderByDesc('id')->limit(100)->get();

        return Inertia::render('Fulfillment::Shipments/Index', [
            'baseUrl' => route('admin.fulfillment.shipments.index'),
            'labels' => __('fulfillment::labels'),
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
            'canManage' => Gate::allows('fulfillment.manage'),
        ]);
    }

    public function store(Request $request, OrderReader $orders, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $data = $request->validate(['order_id' => ['required', 'integer']]);
        abort_if($orders->find((int) $data['order_id']) === null, 404);
        $fulfillment->createForOrder((int) $data['order_id']);

        return back()->with('success', __('fulfillment::messages.created'));
    }

    /**
     * Tạo vận đơn cho các đơn đã chọn ở danh sách đơn (0.3.96): đơn chưa xác nhận hoặc đã có vận đơn được bỏ qua.
     */
    public function bulkCreate(Request $request, OrderReader $orders, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $ids = $this->orderIds($request);
        $created = 0;
        foreach ($ids as $orderId) {
            $order = $orders->find($orderId);
            if ($order === null || ! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing], true)
                || Shipment::query()->where('order_id', $orderId)->where('status', '!=', ShipmentStatus::Cancelled)->exists()) {
                continue;
            }
            try {
                $fulfillment->createForOrder($orderId);
                $created++;
            } catch (BusinessRuleViolation) {
                // Đơn đổi trạng thái giữa chừng: bỏ qua.
            }
        }

        return back()->with('success', __('fulfillment::messages.bulk_created', ['count' => $created, 'skipped' => count($ids) - $created]));
    }

    /**
     * Đánh dấu đã bàn giao cho hãng (đã lấy hàng) các vận đơn đã có mã của đơn đã chọn — hàng rời kho, trừ tồn.
     * Vận đơn chưa có mã được bỏ qua (nhập mã trước).
     */
    public function bulkHandOver(Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $shipments = Shipment::query()->whereIn('order_id', $this->orderIds($request))->where('status', ShipmentStatus::Created)->get();
        $moved = 0;
        foreach ($shipments as $shipment) {
            try {
                $fulfillment->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'staff:'.now()->format('Uu').':handover:'.$shipment->id, 'staff', null, strict: true);
                $moved++;
            } catch (BusinessRuleViolation) {
                // Vận đơn vừa đổi trạng thái: bỏ qua.
            }
        }

        return back()->with('success', __('fulfillment::messages.bulk_handed_over', ['count' => $moved]));
    }

    /**
     * @return list<int>
     */
    private function orderIds(Request $request): array
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1']]);

        return array_values(array_unique(array_map('intval', $data['ids'])));
    }

    public function book(Shipment $shipment, Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'], 'service_code' => ['nullable', 'string', 'max:64']]);
        $fulfillment->book($shipment->id, $data['tracking_number'], $data['service_code'] ?? null);

        return back()->with('success', __('fulfillment::messages.booked'));
    }

    public function status(Shipment $shipment, Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn (ShipmentStatus $status): string => $status->value, self::ACCEPTED_STATUSES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $fulfillment->updateStatus($shipment->id, ShipmentStatus::from($data['status']), 'staff:'.now()->format('Uu').':'.$data['status'], 'staff', $data['note'] ?? null, strict: true);

        return back()->with('success', __('fulfillment::messages.updated'));
    }

    public function cancel(Shipment $shipment, Request $request, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $fulfillment->cancel($shipment->id, $data['reason']);

        return back()->with('success', __('fulfillment::messages.cancelled'));
    }
}
