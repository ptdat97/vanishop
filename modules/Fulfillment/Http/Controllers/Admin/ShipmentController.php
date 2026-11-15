<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Fulfillment\Application\CarrierHandoff;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Contracts\FulfillmentRejected;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Shared\Support\SimpleXlsx;
use Modules\Shared\Support\SpreadsheetRows;
use Modules\Shared\Support\StoreClock;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ShipmentController
{
    /** Số dòng mỗi trang (mặc định là giá trị đầu) — như danh sách đơn hàng. */
    private const PER_PAGE_OPTIONS = [30, 50, 100];

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

    public function index(Request $request, OrderReader $orders, CarrierRegistry $registry, AdminScreen $screen): Response
    {
        Gate::authorize('fulfillment.view');
        $extensionFilters = array_filter((array) $request->query('ext', []), 'is_string');
        $extensionIds = $screen->filterIds('shipment', $extensionFilters);
        $status = (string) $request->query('status', '');
        $number = strtoupper(trim((string) $request->query('order', '')));
        $order = $number === '' ? null : $orders->findByNumber($number);

        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : self::PER_PAGE_OPTIONS[0];
        $page = Shipment::query()->with('lines')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($number !== '', fn ($query) => $query->where('order_id', $order->id ?? 0))
            ->when($extensionIds !== null, fn ($query) => $query->whereIn('id', $extensionIds))
            ->orderByDesc('id')->paginate($perPage)->withQueryString();
        $shipments = collect($page->items());
        $numbers = $orders->numbers($shipments->pluck('order_id')->map(fn ($id): int => (int) $id)->all());

        return Inertia::render('Fulfillment::Shipments/Index', [
            'baseUrl' => route('admin.fulfillment.shipments.index'),
            'labels' => __('fulfillment::labels'),
            'filters' => ['status' => $status, 'order' => $number],
            'statuses' => array_column(ShipmentStatus::cases(), 'value'),
            'order' => $order === null ? null : [
                'id' => $order->id, 'number' => $order->number, 'status' => $order->status->value,
                'status_label' => (string) __("ordering::labels.order_status.{$order->status->value}"),
                'can_create' => in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Processing], true)
                    && ! Shipment::query()->where('order_id', $order->id)->where('status', '!=', ShipmentStatus::Cancelled)->exists(),
            ],
            'shipments' => $shipments->map(fn (Shipment $shipment): array => [
                'id' => $shipment->id,
                'order_number' => $numbers[$shipment->order_id] ?? null,
                'order_url' => route('admin.orders.orders.show', ['order' => $shipment->order_id]),
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
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'canManage' => Gate::allows('fulfillment.manage'),
            // Cột/lọc/thao tác hàng loạt/công cụ tải file của plugin (EP v3 X1).
            'extensions' => $screen->listProps('shipment', $shipments->pluck('id')->map(fn ($id): int => (int) $id)->all(), $extensionFilters),
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
     * "Đẩy lên hãng" hàng loạt từ danh sách đơn (ADR-046 chốt ②): vận đơn chờ đặt/đặt lỗi của hãng có API thuộc các đơn
     * đã chọn. Vận đơn của hãng nhập tay hoặc đã có mã được bỏ qua.
     */
    public function bulkPush(Request $request, FulfillmentService $fulfillment, OrderReader $orders): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $shipments = Shipment::query()->whereIn('order_id', $this->orderIds($request))->where('status', '!=', ShipmentStatus::Cancelled)->get();
        $pushed = $fulfillment->pushToCarrier($shipments->pluck('id')->map(fn ($id): int => (int) $id)->all());
        // Vận đơn bị plugin chặn (vani.fulfillment.before_push): báo lý do theo đơn.
        $numbers = $orders->numbers($shipments->pluck('order_id')->map(fn ($id): int => (int) $id)->all());
        $blocked = [];
        foreach ($shipments->whereIn('status', [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed]) as $shipment) {
            if (($reasons = $fulfillment->pushBlockers($shipment)) !== []) {
                $blocked[] = ($numbers[$shipment->order_id] ?? "#{$shipment->order_id}").': '.implode('; ', $reasons);
            }
        }
        $redirect = back()->with('success', __('fulfillment::messages.pushed', ['count' => $pushed, 'skipped' => $shipments->count() - $pushed]));

        return $blocked === [] ? $redirect : $redirect->withErrors(['business' => implode(' · ', array_slice($blocked, 0, 5))]);
    }

    public function push(Shipment $shipment, FulfillmentService $fulfillment): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        if (($reasons = $fulfillment->pushBlockers($shipment)) !== []) {
            throw FulfillmentRejected::pushBlocked($reasons);
        }
        $pushed = $fulfillment->pushToCarrier([$shipment->id]);
        if ($pushed === 0) {
            throw FulfillmentRejected::invalidTransition($shipment->status->value, ShipmentStatus::Created->value);
        }

        return back()->with('success', __('fulfillment::messages.pushed', ['count' => 1, 'skipped' => 0]));
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
     * File .xlsx gửi hãng vận chuyển (0.3.97): vận đơn chưa rời kho của các đơn đã chọn — nhân viên dán vào mẫu của hãng.
     */
    public function export(Request $request, CarrierHandoff $handoff): BinaryFileResponse
    {
        Gate::authorize('fulfillment.view');
        $path = tempnam(sys_get_temp_dir(), 'vani-handoff-').'.xlsx';
        SimpleXlsx::write($path, $handoff->rows($this->orderIds($request)), 'Giao hang');

        return response()->download($path, 'don-giao-hang-'.now(StoreClock::timezone())->format('Ymd-Hi').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * Nhập mã vận đơn từ file (.xlsx hãng xuất, hoặc file xuất ở trên đã điền; .csv) — 0.3.97.
     */
    public function importTracking(Request $request, CarrierHandoff $handoff): RedirectResponse
    {
        Gate::authorize('fulfillment.manage');
        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv,txt'],
            'carrier' => ['nullable', 'string', 'max:64'],
        ]);
        $file = $request->file('file');
        try {
            $rows = SpreadsheetRows::fromUpload($file);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $result = $handoff->import($rows, $data['carrier'] ?? null);

        $redirect = back()->with('success', __('fulfillment::messages.tracking_imported', ['booked' => $result['booked'], 'unchanged' => $result['unchanged'], 'errors' => count($result['errors'])]));

        // Mỗi dòng lỗi một khoá (Inertia chỉ chuyển câu đầu tiên của mỗi khoá).
        $errors = [];
        foreach (array_slice($result['errors'], 0, 30) as $index => $message) {
            $errors["import.{$index}"] = $message;
        }

        return $errors === [] ? $redirect : $redirect->withErrors($errors);
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
        if (($reasons = $fulfillment->pushBlockers($shipment)) !== []) {
            throw FulfillmentRejected::pushBlocked($reasons);
        }
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
