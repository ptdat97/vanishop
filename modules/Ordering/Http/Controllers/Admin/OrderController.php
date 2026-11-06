<?php

declare(strict_types=1);

namespace Modules\Ordering\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Checkout\Contracts\ShippingAddresses;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Facades\Hook;
use Modules\Ordering\Application\CustomerStatusLabel;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Application\OrderQueries;
use Modules\Ordering\Contracts\Data\LineCancellationCause;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Domain\OrderStage;
use Modules\Ordering\Domain\OrderStateMachine;
use Modules\Ordering\Domain\PaymentStatus;
use Modules\Ordering\Http\Requests\ShippingAddressRequest;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Shared\Support\StoreClock;

final class OrderController
{
    public function home(): RedirectResponse
    {
        Gate::authorize('orders.view');

        return redirect()->route('admin.orders.orders.index');
    }

    public function index(Request $request, OrderQueries $queries, AdminScreen $screen): Response
    {
        Gate::authorize('orders.view');
        $keys = ['stage', 'status', 'payment_status', 'payment_method', 'source', 'from', 'to', 'q'];
        $filters = array_map(fn (mixed $value): string => is_scalar($value) ? (string) $value : '', array_replace(array_fill_keys($keys, ''), $request->only($keys)));
        $extensionFilters = (array) $request->query('ext', []);
        $scoped = [...$filters, 'ids' => $screen->filterIds('order', $extensionFilters)];
        $page = $queries->search($scoped);
        $ids = collect($page->items())->pluck('id')->all();

        // Cột và thao tác hàng loạt: plugin (AdminScreen) + module Core (slot vani.admin.order.list_columns / bulk_actions).
        $extensionColumns = $screen->columns('order', $ids);
        foreach (array_filter(Hook::slot('vani.admin.order.list_columns', $ids)) as $column) {
            $extensionColumns['columns'][] = ['key' => $column['key'], 'label' => $column['label']];
            foreach ($column['values'] as $orderId => $text) {
                $extensionColumns['values'][$orderId][$column['key']] = $text;
            }
        }
        $actions = Gate::allows('orders.manage') ? [['key' => 'confirm', 'label' => 'Xác nhận đơn', 'url' => route('admin.orders.orders.bulk-confirm'), 'confirm' => true]] : [];
        foreach (array_filter(Hook::slot('vani.admin.order.bulk_actions'), 'is_array') as $contributed) {
            array_push($actions, ...$contributed);
        }
        array_push($actions, ...$screen->actions('order', 'bulk'));

        return Inertia::render('Ordering::Orders/Index', [
            'baseUrl' => route('admin.orders.orders.index'),
            'filters' => $filters,
            'stages' => OrderStage::STAGES,
            'actionableStages' => OrderStage::ACTIONABLE,
            'stageCounts' => $queries->stageCounts($scoped),
            'statuses' => array_column(OrderStatus::cases(), 'value'),
            'paymentStatuses' => PaymentStatus::VALUES,
            'labels' => [...__('ordering::labels'), 'payment_method' => $this->paymentMethodLabels()],
            'orders' => collect($page->items())->map(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'placed_at' => StoreClock::format($order->placed_at),
                'age_hours' => $order->placed_at === null ? null : (int) floor($order->placed_at->diffInMinutes(now()) / 60),
                'customer' => (string) ($order->customer_snapshot['full_name'] ?? ''),
                'phone' => (string) $order->customer_phone,
                'province' => (string) ($order->shipping_address['province_name'] ?? ''),
                'items' => $order->lines->map(fn ($line): array => ['sku' => $line->sku, 'quantity' => $line->quantity - $line->cancelled_quantity])->filter(fn (array $line): bool => $line['quantity'] > 0)->values()->all(),
                'total' => $order->total_amount,
                'stage' => (string) $order->getAttribute('stage'),
                'order_status' => $order->order_status->value,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'source' => $order->source,
                'label' => CustomerStatusLabel::of($order->order_status->value, (string) $order->payment_status, (string) $order->fulfillment_status, (string) $order->return_status)['label'],
            ])->all(),
            'pagination' => ['current' => $page->currentPage(), 'last' => $page->lastPage(), 'total' => $page->total()],
            'extensions' => [
                ...$extensionColumns,
                'filters' => $screen->filters('order'),
                'filterValues' => $extensionFilters,
                'actions' => $actions,
            ],
        ]);
    }

    public function show(Order $order, OrderQueries $queries, AdminScreen $screen, ShippingAddresses $addresses): Response
    {
        Gate::authorize('orders.view');
        $detail = $queries->detail($order->loadMissing(['lines', 'adjustments']));
        $canManage = Gate::allows('orders.manage');

        return Inertia::render('Ordering::Orders/Show', [
            'baseUrl' => route('admin.orders.orders.index'),
            'order' => (array) $detail,
            'labels' => __('ordering::labels'),
            'panels' => array_values(array_filter(Hook::slot('vani.admin.order.sidebar', $detail))),
            'can' => [
                'confirm' => $canManage && OrderStateMachine::can($order->order_status, OrderStatus::Confirmed),
                'cancel' => Gate::allows('orders.cancel') && OrderPolicy::staffCanCancel($order->order_status, (string) $order->fulfillment_status),
                'cancel_lines' => Gate::allows('orders.cancel') && OrderPolicy::staffCanCancelLines($order->order_status, (string) $order->fulfillment_status, (string) $order->payment_status),
                'change_address' => $canManage && OrderPolicy::canChangeAddress($order->order_status, (string) $order->fulfillment_status),
                'note' => $canManage,
            ],
            'extensions' => ['actions' => $screen->actions('order', 'detail'), 'tabs' => $screen->tabs('order', $order->id)],
            // Có danh mục địa giới: form đổi địa chỉ chọn tỉnh/phường (phường tải qua Storefront API).
            'addressDirectory' => ($directory = $addresses->directory()) === null ? null : [
                'provinces' => $directory->provinces(),
                'wards' => ($code = (string) ($detail->shippingAddress['province_code'] ?? '')) === '' ? [] : $directory->wards($code),
                'wards_url' => url('/api/storefront/v1/address/provinces'),
            ],
        ]);
    }

    /**
     * Cách thanh toán cho bộ lọc/cột: mã đã có trong đơn (kể cả cổng đã gỡ) + nhãn do Payment cung cấp qua slot
     * `vani.admin.order.payment_methods` (Ordering không biết cổng nào được cài).
     *
     * @return array<string, string>
     */
    private function paymentMethodLabels(): array
    {
        $labels = [];
        foreach (Order::query()->distinct()->pluck('payment_method')->filter() as $code) {
            $labels[(string) $code] = (string) $code;
        }
        foreach (array_filter(Hook::slot('vani.admin.order.payment_methods'), 'is_array') as $contributed) {
            $labels = array_replace($labels, $contributed);
        }

        return $labels;
    }

    /**
     * Xác nhận hàng loạt (danh sách đơn): đơn không còn chờ xác nhận được bỏ qua, không làm hỏng cả lô.
     */
    public function bulkConfirm(Request $request, OrderCommands $commands): RedirectResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1']]);

        $confirmed = 0;
        foreach (Order::query()->whereIn('id', $data['ids'])->get() as $order) {
            if (! OrderStateMachine::can($order->order_status, OrderStatus::Confirmed)) {
                continue;
            }
            try {
                $commands->confirm($order->id, 'staff_confirmed_bulk');
                $confirmed++;
            } catch (BusinessRuleViolation) {
                // Đơn vừa đổi trạng thái ở nơi khác: bỏ qua.
            }
        }

        return back()->with('success', __('ordering::messages.bulk_confirmed', ['count' => $confirmed, 'skipped' => count($data['ids']) - $confirmed]));
    }

    public function confirm(Order $order, Request $request, OrderCommands $commands): RedirectResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $commands->confirm($order->id, 'staff_confirmed'.(isset($data['reason']) ? ": {$data['reason']}" : ''));

        return back()->with('success', __('ordering::messages.confirmed'));
    }

    public function cancel(Order $order, Request $request, OrderCommands $commands): RedirectResponse
    {
        Gate::authorize('orders.cancel');
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $commands->cancel($order->id, $data['reason'], 'staff');

        return back()->with('success', __('ordering::messages.cancelled'));
    }

    public function cancelLines(Order $order, Request $request, OrderCommands $commands): RedirectResponse
    {
        Gate::authorize('orders.cancel');
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['integer', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
            'cause' => ['nullable', Rule::enum(LineCancellationCause::class)],
            'lock_version' => ['required', 'integer', 'min:0'],
        ]);
        $quantities = [];
        foreach ($data['lines'] as $lineId => $quantity) {
            $quantities[(int) $lineId] = (int) $quantity;
        }
        $commands->cancelLines($order->id, $quantities, $data['reason'], (int) $data['lock_version'], LineCancellationCause::from($data['cause'] ?? 'shop'));

        return back()->with('success', __('ordering::messages.lines_cancelled'));
    }

    public function updateAddress(Order $order, ShippingAddressRequest $request, OrderCommands $commands): RedirectResponse
    {
        $address = $request->safe()->only(['province_code', 'province_name', 'ward_code', 'ward_name', 'street_line']);
        $commands->changeShippingAddress($order->id, $address, (string) $request->validated('reason'), (int) $request->validated('lock_version'));

        return back()->with('success', __('ordering::messages.address_changed'));
    }

    public function addNote(Order $order, Request $request, OrderCommands $commands): RedirectResponse
    {
        Gate::authorize('orders.manage');
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $commands->addNote($order->id, $data['note']);

        return back()->with('success', __('ordering::messages.note_added'));
    }
}
