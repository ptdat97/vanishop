<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Customer\Application\CustomerSegmentService;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Modules\Identity\Contracts\Data\ScopeRef;

/**
 * Nhóm khách (roadmap Phase 8): gắn bảng giá thành viên ở Admin → Bảng giá.
 */
final class CustomerGroupController
{
    public function index(): Response
    {
        Gate::authorize('customers.view', [ScopeRef::owner()]);

        return Inertia::render('Customer::Customers/Groups', [
            'baseUrl' => route('admin.customer-groups.index'),
            'customersUrl' => route('admin.customers.index'),
            'groups' => CustomerGroup::query()->withCount('customers')->orderBy('position')->orderBy('name')->get()->map(fn (CustomerGroup $group): array => [
                'id' => $group->id, 'code' => $group->code, 'name' => $group->name, 'description' => $group->description,
                'position' => $group->position, 'customers_count' => (int) $group->getAttribute('customers_count'),
            ])->all(),
            'canManage' => Gate::allows('customers.segment', [ScopeRef::owner()]),
        ]);
    }

    public function store(Request $request, CustomerSegmentService $segments): RedirectResponse
    {
        Gate::authorize('customers.segment', [ScopeRef::owner()]);
        $segments->saveGroup($this->validated($request));

        return back()->with('success', __('customer::messages.group_saved'));
    }

    public function update(int $group, Request $request, CustomerSegmentService $segments): RedirectResponse
    {
        Gate::authorize('customers.segment', [ScopeRef::owner()]);
        $model = CustomerGroup::query()->findOrFail($group);
        $segments->saveGroup($this->validated($request, $model), $model);

        return back()->with('success', __('customer::messages.group_saved'));
    }

    public function destroy(int $group, CustomerSegmentService $segments): RedirectResponse
    {
        Gate::authorize('customers.segment', [ScopeRef::owner()]);
        $segments->deleteGroup(CustomerGroup::query()->findOrFail($group));

        return back()->with('success', __('customer::messages.group_deleted'));
    }

    /**
     * @return array{code: string, name: string, description: ?string, position: int}
     */
    private function validated(Request $request, ?CustomerGroup $group = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9][a-z0-9_-]*$/', Rule::unique('customer_groups', 'code')->ignore($group?->id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        return ['code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'] ?? null, 'position' => (int) ($data['position'] ?? 0)];
    }
}
