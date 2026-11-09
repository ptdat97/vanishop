<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\ScopeOptions;
use Modules\Identity\Application\StaffAdministration;
use Modules\Identity\Http\Requests\StaffRequest;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Admin → Nhân viên (reference-parity §3.3-1): mời, sửa tên + vai trò theo phạm vi, khoá/mở khoá. Không xoá cứng.
 */
final class StaffController
{
    public function index(Request $request): Response
    {
        Gate::authorize('staff.manage');
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', Rule::in([25, 50, 100])],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 25);
        $search = trim((string) $request->query('q', ''));
        $role = (int) $request->query('role', 0);
        $status = (string) $request->query('status', '');

        $staff = StaffUser::query()
            ->with('roleAssignments.role')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($role > 0, fn ($query) => $query->whereHas('roleAssignments', fn ($query) => $query->where('role_id', $role)))
            ->when(in_array($status, [StaffUser::STATUS_ACTIVE, StaffUser::STATUS_INVITED, StaffUser::STATUS_LOCKED], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Identity::Staff/Index', [
            'baseUrl' => route('admin.staff.index'),
            'labels' => __('identity::labels'),
            'rolesUrl' => route('admin.staff.roles.index'),
            'filters' => ['q' => $search, 'role' => $role ?: null, 'status' => $status ?: null],
            'roles' => Role::query()->orderBy('name')->get(['id', 'name'])->all(),
            'staff' => [
                'data' => $staff->getCollection()->map(fn (StaffUser $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'status' => $member->status,
                    'roles' => $member->roleAssignments->map(fn ($assignment): string => $assignment->role->name)->unique()->values()->all(),
                    'last_login_at' => $member->last_login_at?->toIso8601String(),
                ])->all(),
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'from' => $staff->firstItem(),
                'to' => $staff->lastItem(),
                'total' => $staff->total(),
                'per_page' => $staff->perPage(),
            ],
        ]);
    }

    public function create(ScopeOptions $scopes): Response
    {
        Gate::authorize('staff.manage');

        return $this->form(null, [], $scopes);
    }

    public function store(StaffRequest $request, StaffAdministration $administration): RedirectResponse
    {
        $staff = $administration->invite($this->actor($request), (string) $request->validated('name'), (string) $request->validated('email'), $request->assignments());

        return redirect()->route('admin.staff.edit', $staff)->with('success', __('identity::staff.invited', ['email' => $staff->email]));
    }

    public function edit(StaffUser $staff, StaffAdministration $administration, ScopeOptions $scopes): Response
    {
        Gate::authorize('staff.manage');

        return $this->form($staff, $administration->assignmentsOf($staff), $scopes);
    }

    public function update(StaffUser $staff, StaffRequest $request, StaffAdministration $administration): RedirectResponse
    {
        $administration->update($this->actor($request), $staff, (string) $request->validated('name'), $request->assignments());

        return back()->with('success', __('identity::staff.saved'));
    }

    public function lock(StaffUser $staff, Request $request, StaffAdministration $administration): RedirectResponse
    {
        Gate::authorize('staff.manage');
        $administration->lock($this->actor($request), $staff);

        return back()->with('success', __('identity::staff.locked'));
    }

    public function unlock(StaffUser $staff, StaffAdministration $administration): RedirectResponse
    {
        Gate::authorize('staff.manage');
        $administration->unlock($staff);

        return back()->with('success', __('identity::staff.unlocked'));
    }

    public function resendInvite(StaffUser $staff, StaffAdministration $administration): RedirectResponse
    {
        Gate::authorize('staff.manage');
        $administration->resendInvite($staff);

        return back()->with('success', __('identity::staff.invite_resent'));
    }

    /**
     * @param  list<array{role_id: int, scope_type: string, scope_id: int|null}>  $assignments
     */
    private function form(?StaffUser $staff, array $assignments, ScopeOptions $scopes): Response
    {
        return Inertia::render('Identity::Staff/Form', [
            'baseUrl' => route('admin.staff.index'),
            'labels' => __('identity::labels'),
            'staff' => $staff === null ? null : [
                ...$staff->only(['id', 'name', 'email', 'status']),
                'last_login_at' => $staff->last_login_at?->toIso8601String(),
                'is_self' => $staff->is(request()->user('staff')),
            ],
            'assignments' => $assignments,
            'roles' => Role::query()->orderBy('name')->get(['id', 'code', 'name'])->all(),
            'scopes' => $scopes->all(),
        ]);
    }

    private function actor(Request $request): StaffUser
    {
        /** @var StaffUser */
        return $request->user('staff');
    }
}
