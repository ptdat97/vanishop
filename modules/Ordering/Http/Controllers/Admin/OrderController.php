<?php

declare(strict_types=1);

namespace Modules\Ordering\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Checkout\Contracts\ShippingAddresses;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Facades\Hook;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Application\OrderQueries;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Domain\CustomerStatus;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Domain\OrderStateMachine;
use Modules\Ordering\Domain\PaymentStatus;
use Modules\Ordering\Http\Requests\ShippingAddressRequest;
use Modules\Ordering\Persistence\Models\Order;

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
        $filters = $request->only(['status', 'payment_status', 'q']);
        $extensionFilters = (array) $request->query('ext', []);
        $page = $queries->search([...$filters, 'ids' => $screen->filterIds('order', $extensionFilters)]);

        return Inertia::render('Ordering::Orders/Index', [
            'baseUrl' => route('admin.orders.orders.index'),
            'filters' => ['status' => $filters['status'] ?? '', 'payment_status' => $filters['payment_status'] ?? '', 'q' => $filters['q'] ?? ''],
            'statuses' => array_column(OrderStatus::cases(), 'value'),
            'paymentStatuses' => PaymentStatus::VALUES,
            'orders' => collect($page->items())->map(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'placed_at' => $order->placed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'customer' => (string) ($order->customer_snapshot['full_name'] ?? ''),
                'phone' => (string) $order->customer_phone,
                'total' => $order->total_amount,
                'order_status' => $order->order_status->value,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'label' => CustomerStatus::of($order->order_status->value, (string) $order->payment_status, (string) $order->fulfillment_status, (string) $order->return_status)['label'],
            ])->all(),
            'pagination' => ['current' => $page->currentPage(), 'last' => $page->lastPage(), 'total' => $page->total()],
            'extensions' => [
                ...$screen->columns('order', collect($page->items())->pluck('id')->all()),
                'filters' => $screen->filters('order'),
                'filterValues' => $extensionFilters,
                'actions' => $screen->actions('order', 'bulk'),
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
            'lock_version' => ['required', 'integer', 'min:0'],
        ]);
        $quantities = [];
        foreach ($data['lines'] as $lineId => $quantity) {
            $quantities[(int) $lineId] = (int) $quantity;
        }
        $commands->cancelLines($order->id, $quantities, $data['reason'], (int) $data['lock_version']);

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
