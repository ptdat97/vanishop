<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Contracts\StaffRejected;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Admin → Vai trò (reference-parity §3.3-2). Quyền chọn từ PermissionRegistry (gồm quyền plugin khai báo). Vai trò hệ
 * thống (`is_system`, vd. `owner_admin` có `*`) không sửa/xoá được; vai trò đang được gán không xoá được; chỉ thêm được
 * quyền mà người thao tác đang có.
 */
final class RoleAdministration
{
    public function __construct(
        private readonly StaffAdministration $staff,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $permissions
     */
    public function create(StaffUser $actor, string $code, string $name, array $permissions): Role
    {
        $this->staff->assertHolds($actor, $permissions);

        $role = DB::transaction(function () use ($code, $name, $permissions): Role {
            $role = Role::query()->create(['code' => $code, 'name' => $name, 'is_system' => false]);
            $role->syncPermissions($permissions);

            return $role;
        });
        $this->audit->record('identity.role.created', 'role', $role->id, ['code' => $code, 'permissions' => array_values(array_unique($permissions))]);

        return $role;
    }

    /**
     * @param  list<string>  $permissions
     */
    public function update(StaffUser $actor, Role $role, string $name, array $permissions): void
    {
        $this->assertEditable($role);
        $before = $role->permissions()->pluck('permission')->all();
        $this->staff->assertHolds($actor, array_diff($permissions, $before));

        DB::transaction(function () use ($role, $name, $permissions): void {
            $role->forceFill(['name' => $name])->save();
            $role->syncPermissions($permissions);
        });
        $this->audit->record('identity.role.updated', 'role', $role->id, [
            'added' => array_values(array_diff($permissions, $before)),
            'removed' => array_values(array_diff($before, $permissions)),
        ]);
    }

    public function delete(Role $role): void
    {
        $this->assertEditable($role);
        if ($role->assignments()->exists()) {
            throw StaffRejected::roleInUse();
        }

        $role->delete();
        $this->audit->record('identity.role.deleted', 'role', $role->id, ['code' => $role->code]);
    }

    private function assertEditable(Role $role): void
    {
        if ($role->is_system) {
            throw StaffRejected::systemRole();
        }
    }
}
