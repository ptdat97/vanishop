<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\StaffRejected;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\RolePermission;
use Modules\Identity\Persistence\Models\StaffRoleAssignment;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Admin → Nhân viên (reference-parity §3.3-1). Quy tắc:
 * - tạo = mời qua email (link đặt mật khẩu 24 giờ), không đặt mật khẩu hộ; không xoá cứng (chỉ khoá);
 * - không tự khoá, không tự đổi vai trò của chính mình; không khoá/hạ quyền Owner cuối cùng (nhân viên đang hoạt động
 *   có vai trò `*` ở phạm vi Owner);
 * - không cấp vai trò có quyền mà người thao tác không có (chống leo thang quyền).
 *
 * @phpstan-type Assignment array{role_id: int, scope_type: string, scope_id: int|null}
 */
final class StaffAdministration
{
    public function __construct(
        private readonly StaffPasswords $passwords,
        private readonly AuditLogger $audit,
        private readonly Authorizer $authorizer,
    ) {}

    /**
     * @param  list<Assignment>  $assignments
     */
    public function invite(StaffUser $actor, string $name, string $email, array $assignments): StaffUser
    {
        $this->assertCanGrant($actor, $assignments);

        $staff = DB::transaction(function () use ($name, $email, $assignments): StaffUser {
            // Mật khẩu ngẫu nhiên không ai biết: nhân viên `invited` không đăng nhập được, phải đặt qua link.
            $staff = StaffUser::query()->create(['name' => $name, 'email' => $email, 'password' => Str::random(64), 'status' => StaffUser::STATUS_INVITED]);
            $this->syncAssignments($staff, $assignments);

            return $staff;
        });

        $this->passwords->sendInvite($staff);
        $this->audit->record('identity.staff.invited', 'staff_user', $staff->id, ['email' => $email, 'assignments' => $assignments]);

        return $staff;
    }

    public function resendInvite(StaffUser $staff): void
    {
        if ($staff->status !== StaffUser::STATUS_INVITED) {
            return;
        }

        $this->passwords->sendInvite($staff);
        $this->audit->record('identity.staff.invite_resent', 'staff_user', $staff->id);
    }

    /**
     * @param  list<Assignment>  $assignments
     */
    public function update(StaffUser $actor, StaffUser $staff, string $name, array $assignments): void
    {
        $before = $this->assignmentsOf($staff);
        $changed = $this->normalize($assignments) !== $before;
        if ($changed && $actor->is($staff)) {
            throw StaffRejected::self();
        }
        if ($changed) {
            $this->assertCanGrant($actor, $assignments);
        }

        DB::transaction(function () use ($staff, $name, $assignments, $changed): void {
            $staff->forceFill(['name' => $name])->save();
            if ($changed) {
                $this->lockOwners();
                $this->syncAssignments($staff, $assignments);
                $this->assertOwnerRemains();
            }
        });

        $this->audit->record('identity.staff.updated', 'staff_user', $staff->id, $changed ? ['assignments' => ['from' => $before, 'to' => $this->assignmentsOf($staff)]] : ['name' => $name]);
    }

    public function lock(StaffUser $actor, StaffUser $staff): void
    {
        if ($actor->is($staff)) {
            throw StaffRejected::self();
        }

        DB::transaction(function () use ($staff): void {
            $this->lockOwners();
            $staff->forceFill(['status' => StaffUser::STATUS_LOCKED, 'remember_token' => Str::random(60)])->save();
            $this->assertOwnerRemains();
        });
        $this->passwords->revoke($staff);
        $this->audit->record('identity.staff.locked', 'staff_user', $staff->id);
    }

    /**
     * Mở khoá: nhân viên chưa từng đặt mật khẩu (chưa đăng nhập lần nào) quay về `invited` và nhận lời mời mới.
     */
    public function unlock(StaffUser $staff): void
    {
        if ($staff->status !== StaffUser::STATUS_LOCKED) {
            return;
        }

        $neverSignedIn = $staff->last_login_at === null;
        $staff->forceFill(['status' => $neverSignedIn ? StaffUser::STATUS_INVITED : StaffUser::STATUS_ACTIVE])->save();
        if ($neverSignedIn) {
            $this->passwords->sendInvite($staff);
        }
        $this->audit->record('identity.staff.unlocked', 'staff_user', $staff->id);
    }

    public function isOwner(StaffUser $staff): bool
    {
        return $this->ownerQuery()->whereKey($staff->id)->exists();
    }

    /**
     * @return list<Assignment>
     */
    public function assignmentsOf(StaffUser $staff): array
    {
        return $this->normalize($staff->roleAssignments()->get(['role_id', 'scope_type', 'scope_id'])->map(fn (StaffRoleAssignment $assignment): array => [
            'role_id' => $assignment->role_id,
            'scope_type' => $assignment->scope_type->value,
            'scope_id' => $assignment->scope_id,
        ])->all());
    }

    /**
     * Người thao tác phải có mọi quyền được cấp (quyền `*` → chỉ Owner cấp được). Dùng cả khi sửa quyền của vai trò.
     *
     * @param  iterable<string>  $permissions
     */
    public function assertHolds(StaffUser $actor, iterable $permissions): void
    {
        foreach ($permissions as $permission) {
            $allowed = $permission === PermissionRegistry::WILDCARD ? $this->isOwner($actor) : $this->authorizer->allows($actor->id, $permission);
            if (! $allowed) {
                throw StaffRejected::exceedsOwnPermissions();
            }
        }
    }

    /**
     * @param  list<Assignment>  $assignments
     */
    private function assertCanGrant(StaffUser $actor, array $assignments): void
    {
        $this->assertHolds($actor, RolePermission::query()->whereIn('role_id', array_column($assignments, 'role_id'))->pluck('permission')->unique()->all());
    }

    /**
     * @param  list<Assignment>  $assignments
     */
    private function syncAssignments(StaffUser $staff, array $assignments): void
    {
        $staff->roleAssignments()->delete();
        foreach ($this->normalize($assignments) as $assignment) {
            $staff->roleAssignments()->create($assignment);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @return list<Assignment>
     */
    private function normalize(array $assignments): array
    {
        $normalized = array_map(fn (array $assignment): array => [
            'role_id' => (int) $assignment['role_id'],
            'scope_type' => (string) $assignment['scope_type'],
            'scope_id' => ($assignment['scope_type'] ?? null) === ScopeType::Owner->value || ($assignment['scope_id'] ?? null) === null ? null : (int) $assignment['scope_id'],
        ], $assignments);
        $normalized = array_values(array_unique($normalized, SORT_REGULAR));
        usort($normalized, fn (array $a, array $b): int => [$a['role_id'], $a['scope_type'], $a['scope_id']] <=> [$b['role_id'], $b['scope_type'], $b['scope_id']]);

        return $normalized;
    }

    /** Khoá các Owner đang hoạt động: hai quản trị khoá/hạ quyền nhau cùng lúc không làm mất Owner cuối. */
    private function lockOwners(): void
    {
        $this->ownerQuery()->lockForUpdate()->pluck('staff_users.id');
    }

    private function assertOwnerRemains(): void
    {
        if (! $this->ownerQuery()->exists()) {
            throw StaffRejected::lastOwner();
        }
    }

    /**
     * @return Builder<StaffUser>
     */
    private function ownerQuery(): Builder
    {
        return StaffUser::query()
            ->where('status', StaffUser::STATUS_ACTIVE)
            ->whereHas('roleAssignments', fn ($query) => $query
                ->where('scope_type', ScopeType::Owner->value)
                ->whereIn('role_id', RolePermission::query()->select('role_id')->where('permission', PermissionRegistry::WILDCARD)));
    }
}
