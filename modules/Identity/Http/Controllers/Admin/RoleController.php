<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Application\RoleAdministration;
use Modules\Identity\Http\Requests\RoleRequest;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Admin → Vai trò (reference-parity §3.3-2): quyền nhóm theo tiền tố mã (`catalog.*`, `orders.*`…), gồm quyền plugin.
 */
final class RoleController
{
    public function index(): Response
    {
        Gate::authorize('staff.manage');

        return Inertia::render('Identity::Roles/Index', [
            'baseUrl' => route('admin.staff.roles.index'),
            'staffUrl' => route('admin.staff.index'),
            'roles' => Role::query()->withCount(['permissions', 'assignments'])->orderBy('name')->get()->map(fn (Role $role): array => [
                ...$role->only(['id', 'code', 'name', 'is_system']),
                'permissions_count' => $role->permissions_count,
                'assignments_count' => $role->assignments_count,
            ])->all(),
        ]);
    }

    public function create(PermissionRegistry $registry): Response
    {
        Gate::authorize('staff.manage');

        return $this->form(null, $registry);
    }

    public function store(RoleRequest $request, RoleAdministration $roles): RedirectResponse
    {
        $role = $roles->create($this->actor($request), (string) $request->validated('code'), (string) $request->validated('name'), $request->permissions());

        return redirect()->route('admin.staff.roles.edit', $role)->with('success', __('identity::staff.role_saved'));
    }

    public function edit(Role $role, PermissionRegistry $registry): Response
    {
        Gate::authorize('staff.manage');

        return $this->form($role, $registry);
    }

    public function update(Role $role, RoleRequest $request, RoleAdministration $roles): RedirectResponse
    {
        $roles->update($this->actor($request), $role, (string) $request->validated('name'), $request->permissions());

        return back()->with('success', __('identity::staff.role_saved'));
    }

    public function destroy(Role $role, RoleAdministration $roles): RedirectResponse
    {
        Gate::authorize('staff.manage');
        $roles->delete($role);

        return redirect()->route('admin.staff.roles.index')->with('success', __('identity::staff.role_deleted'));
    }

    private function form(?Role $role, PermissionRegistry $registry): Response
    {
        $groups = [];
        foreach ($registry->all() as $code => $label) {
            $groups[explode('.', $code)[0]][] = ['code' => $code, 'label' => $label];
        }

        return Inertia::render('Identity::Roles/Form', [
            'baseUrl' => route('admin.staff.roles.index'),
            'role' => $role === null ? null : [
                ...$role->only(['id', 'code', 'name', 'is_system']),
                'permissions' => $role->permissions()->pluck('permission')->all(),
                'assigned' => $role->assignments()->exists(),
            ],
            'groups' => array_map(fn (string $group, array $permissions): array => ['key' => $group, 'permissions' => $permissions], array_keys($groups), array_values($groups)),
        ]);
    }

    private function actor(Request $request): StaffUser
    {
        /** @var StaffUser */
        return $request->user('staff');
    }
}
