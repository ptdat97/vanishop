<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Support\Collection;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\StaffRoleAssignment;

/**
 * RBAC theo phạm vi: quyền = permission của vai trò ∧ phạm vi gán bao phủ phạm vi đối tượng (nếu có).
 * Owner bao phủ mọi thứ; LegalEntity bao phủ chính nó và các brand thuộc nó; Brand chỉ bao phủ chính nó.
 */
final class ScopedRbacAuthorizer implements Authorizer
{
    /** @var array<int, Collection<int, StaffRoleAssignment>> */
    private array $assignments = [];

    public function __construct(private readonly BrandDirectory $brands) {}

    public function allows(int $staffUserId, string $permission, ?ScopeRef $target = null): bool
    {
        $granting = $this->assignmentsOf($staffUserId)
            ->filter(fn (StaffRoleAssignment $assignment): bool => $this->grants($assignment, $permission));

        if ($target === null) {
            return $granting->isNotEmpty();
        }

        return $granting->contains(fn (StaffRoleAssignment $assignment): bool => $this->covers($assignment, $target));
    }

    public function accessibleBrandIds(int $staffUserId): ?array
    {
        $assignments = $this->assignmentsOf($staffUserId);

        if ($assignments->contains(fn (StaffRoleAssignment $a): bool => $a->scope_type === ScopeType::Owner)) {
            return null;
        }

        $ids = [];
        foreach ($assignments as $assignment) {
            $ids = [...$ids, ...match ($assignment->scope_type) {
                ScopeType::Brand => [(int) $assignment->scope_id],
                ScopeType::LegalEntity => $this->brands->idsOfLegalEntity((int) $assignment->scope_id),
                ScopeType::Owner => [],
            }];
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
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
            ScopeType::LegalEntity => match ($target->type) {
                ScopeType::LegalEntity => $target->id === $assignment->scope_id,
                ScopeType::Brand => $target->id !== null
                    && $this->brands->find($target->id)?->legalEntityId === $assignment->scope_id,
                ScopeType::Owner => false,
            },
            ScopeType::Brand => $target->type === ScopeType::Brand && $target->id === $assignment->scope_id,
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
