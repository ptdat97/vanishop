<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Collection;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Identity\Persistence\Models\RolePermission;
use Modules\Identity\Persistence\Models\StaffRoleAssignment;

/**
 * RBAC toàn cửa hàng: nhân viên có quyền khi một vai trò được gán có quyền đó (hoặc `*`). Không giới hạn theo kho
 * (Owner 2026-11-19: phân quyền theo team ở mức module).
 */
final class RbacAuthorizer implements Authorizer
{
    /** @var array<int, Collection<int, string>> */
    private array $permissions = [];

    public function allows(int $staffUserId, string $permission): bool
    {
        $granted = $this->permissions[$staffUserId] ??= RolePermission::query()
            ->whereIn('role_id', StaffRoleAssignment::query()->select('role_id')->where('staff_user_id', $staffUserId))
            ->pluck('permission');

        return $granted->contains(PermissionRegistry::WILDCARD) || $granted->contains($permission);
    }
}
