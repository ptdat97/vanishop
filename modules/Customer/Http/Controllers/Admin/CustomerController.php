<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Customer\Application\AccountLifecycle;
use Modules\Customer\Application\AddressBook;
use Modules\Customer\Application\ConsentService;
use Modules\Customer\Application\CustomerQueries;
use Modules\Customer\Application\CustomerStats;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;

/**
 * Khách hàng của cửa hàng.
 */
final class CustomerController
{
    public function index(Request $request, CustomerQueries $queries): Response
    {
        Gate::authorize('customers.view', [ScopeRef::owner()]);
        $q = is_string($request->query('q')) ? $request->query('q') : null;
        $status = is_string($request->query('status')) ? $request->query('status') : null;
        $page = $queries->search($q, $status);

        return Inertia::render('Customer::Customers/Index', [
            'baseUrl' => route('admin.customers.index'),
            'filters' => ['q' => $q, 'status' => $status],
            'statuses' => array_column(CustomerStatus::cases(), 'value'),
            'customers' => collect($page->items())->map(fn (Customer $customer): array => $this->row($customer))->all(),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(string $customer, CustomerQueries $queries, AddressBook $addresses, ConsentService $consents, CustomerStats $stats, CustomerOrders $orders): Response
    {
        Gate::authorize('customers.view', [ScopeRef::owner()]);
        $model = $queries->byPublicId($customer) ?? abort(404);

        return Inertia::render('Customer::Customers/Show', [
            'baseUrl' => route('admin.customers.index'),
            'customer' => [
                ...$this->row($model),
                'birth_date' => $model->birth_date?->format('d/m/Y'),
                'gender' => $model->gender,
                'last_login_at' => $model->last_login_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'merged_into' => $model->merged_into_id === null ? null : Customer::query()->whereKey($model->merged_into_id)->value('public_id'),
            ],
            'addresses' => $addresses->all($model->id),
            'consents' => $consents->all($model->id),
            'stats' => $stats->of($model),
            'orders' => array_map(fn (OrderDetail $order): array => [
                'number' => $order->number, 'status' => $order->customerStatus['label'], 'total' => $order->amounts['total'],
                'placed_at' => $order->placedAt,
            ], $orders->ofCustomer($model->id, 1, 20)['data']),
            'can' => [
                'merge' => Gate::allows('customers.merge', [ScopeRef::owner()]),
                'anonymize' => Gate::allows('customers.anonymize', [ScopeRef::owner()]),
            ],
        ]);
    }

    /**
     * Hợp nhất khách đang xem (nguồn) vào khách đích.
     */
    public function merge(string $customer, Request $request, CustomerQueries $queries, AccountLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('customers.merge', [ScopeRef::owner()]);
        $data = $request->validate(['target' => ['required', 'string', 'size:26']]);
        $source = $queries->byPublicId($customer) ?? abort(404);
        $target = $queries->byPublicId($data['target']) ?? throw ValidationException::withMessages(['target' => 'Không tìm thấy khách đích.']);

        $lifecycle->merge($source->id, $target->id);

        return redirect()->route('admin.customers.show', $target->public_id)->with('success', __('customer::messages.merged'));
    }

    public function anonymize(string $customer, CustomerQueries $queries, AccountLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('customers.anonymize', [ScopeRef::owner()]);
        $model = $queries->byPublicId($customer) ?? abort(404);
        $lifecycle->anonymize($model->id, 'staff');

        return back()->with('success', __('customer::messages.anonymized'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Customer $customer): array
    {
        return [
            'id' => $customer->public_id, 'phone' => $customer->phone, 'email' => $customer->email, 'full_name' => $customer->full_name,
            'status' => $customer->status->value, 'registered' => $customer->isRegistered(),
            'created_at' => $customer->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
        ];
    }
}
