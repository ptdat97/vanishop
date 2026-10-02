<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Collection;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\StaffRoleAssignment;

/**
 * RBAC theo phạm vi: quyền = permission của vai trò ∧ phạm vi gán bao phủ phạm vi đối tượng (nếu có).
 * Owner bao phủ mọi thứ; Location chỉ bao phủ chính location đó.
 */
final class ScopedRbacAuthorizer implements Authorizer
{
    /** @var array<int, Collection<int, StaffRoleAssignment>> */
    private array $assignments = [];

    public function allows(int $staffUserId, string $permission, ?ScopeRef $target = null): bool
    {
        $granting = $this->assignmentsOf($staffUserId)
            ->filter(fn (StaffRoleAssignment $assignment): bool => $this->grants($assignment, $permission));

        if ($target === null) {
            return $granting->isNotEmpty();
        }

        return $granting->contains(fn (StaffRoleAssignment $assignment): bool => $this->covers($assignment, $target));
    }

    private function grants(StaffRoleAssignment $assignment, string $permission): bool
    {
        $granted = $assignment->role->permissions->pluck('permission');

        return $granted->contains(PermissionRegistry::WILDCARD) || $granted->contains($permission);
    }

    private function covers(StaffRoleAssignment $assignment, ScopeRef $target): bool
    {
        return match ($assignment->scope_type) {
            ScopeType::Owner => true,
            ScopeType::Location => $target->type === ScopeType::Location && $target->id === $assignment->scope_id,
        };
    }

    /**
     * @return Collection<int, StaffRoleAssignment>
     */
    private function assignmentsOf(int $staffUserId): Collection
    {
        return $this->assignments[$staffUserId] ??= StaffRoleAssignment::query()
            ->with('role.permissions')
            ->where('staff_user_id', $staffUserId)
            ->get();
    }
}
