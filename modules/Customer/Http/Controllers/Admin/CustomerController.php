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
use Modules\Customer\Application\CustomerSegmentService;
use Modules\Customer\Application\CustomerStats;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Shared\Support\StoreClock;

/**
 * Khách hàng của cửa hàng.
 */
final class CustomerController
{
    public function index(Request $request, CustomerQueries $queries, AdminScreen $screen, CustomerSegmentService $segments): Response
    {
        Gate::authorize('customers.view', [ScopeRef::owner()]);
        $q = is_string($request->query('q')) ? $request->query('q') : null;
        $status = is_string($request->query('status')) ? $request->query('status') : null;
        $extensionFilters = (array) $request->query('ext', []);
        $group = is_numeric($request->query('group')) ? (int) $request->query('group') : null;
        $tag = is_string($request->query('tag')) && $request->query('tag') !== '' ? (string) $request->query('tag') : null;
        $page = $queries->search($q, $status, ids: $screen->filterIds('customer', $extensionFilters), groupId: $group, tag: $tag);
        $groupNames = collect($segments->groups())->pluck('name', 'id');

        return Inertia::render('Customer::Customers/Index', [
            'baseUrl' => route('admin.customers.index'),
            'filters' => ['q' => $q, 'status' => $status, 'group' => $group, 'tag' => $tag],
            'statuses' => array_column(CustomerStatus::cases(), 'value'),
            'groups' => $segments->groups(),
            'groupsUrl' => route('admin.customer-groups.index'),
            'customers' => collect($page->items())->map(fn (Customer $customer): array => [...$this->row($customer), 'group' => $groupNames[$customer->customer_group_id] ?? null])->all(),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'extensions' => [
                ...$screen->columns('customer', collect($page->items())->pluck('id')->all()),
                'filters' => $screen->filters('customer'),
                'filterValues' => $extensionFilters,
            ],
        ]);
    }

    public function show(string $customer, CustomerQueries $queries, AddressBook $addresses, ConsentService $consents, CustomerStats $stats, CustomerOrders $orders, AdminScreen $screen, CustomerSegmentService $segments): Response
    {
        Gate::authorize('customers.view', [ScopeRef::owner()]);
        $model = $queries->byPublicId($customer) ?? abort(404);

        return Inertia::render('Customer::Customers/Show', [
            'baseUrl' => route('admin.customers.index'),
            'customer' => [
                ...$this->row($model),
                // Ngày sinh là ngày lịch, không đổi múi giờ.
                'birth_date' => $model->birth_date?->format((string) config('vanishop.locale.formats.date')),
                'gender' => $model->gender,
                'last_login_at' => StoreClock::format($model->last_login_at),
                'merged_into' => $model->merged_into_id === null ? null : Customer::query()->whereKey($model->merged_into_id)->value('public_id'),
            ],
            'segment' => ['customer_group_id' => $model->customer_group_id, 'tags' => $segments->tagsOf($model->id), 'groups' => $segments->groups()],
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
                'segment' => Gate::allows('customers.segment', [ScopeRef::owner()]),
            ],
            'extensions' => ['id' => $model->id, 'actions' => $screen->actions('customer', 'detail'), 'tabs' => $screen->tabs('customer', $model->id)],
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
     * Gán nhóm khách + tag (Phase 8). Tag nhập phân tách dấu phẩy, chuẩn hoá thành slug.
     */
    public function segment(string $customer, Request $request, CustomerQueries $queries, CustomerSegmentService $segments): RedirectResponse
    {
        Gate::authorize('customers.segment', [ScopeRef::owner()]);
        $model = $queries->byPublicId($customer) ?? abort(404);
        $data = $request->validate(['customer_group_id' => ['nullable', 'integer'], 'tags' => ['nullable', 'string', 'max:1000']]);
        $segments->assign($model, isset($data['customer_group_id']) ? (int) $data['customer_group_id'] : null, explode(',', (string) ($data['tags'] ?? '')));

        return back()->with('success', __('customer::messages.segment_saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Customer $customer): array
    {
        return [
            'id' => $customer->public_id, 'ref' => $customer->id, 'phone' => $customer->phone, 'email' => $customer->email, 'full_name' => $customer->full_name,
            'status' => $customer->status->value, 'registered' => $customer->isRegistered(),
            'created_at' => StoreClock::format($customer->created_at),
        ];
    }
}
