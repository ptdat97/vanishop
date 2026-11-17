<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Truy vấn đọc cho màn hình Admin của Identity (nhân viên, vai trò) — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * @return Collection<int, Role>
     */
    public function rolesWithUsage(): Collection
    {
        return Role::query()->withCount(['permissions', 'assignments'])->orderBy('name')->get();
    }

    /**
     * Danh sách nhân viên: tìm theo tên/email, lọc vai trò, trạng thái.
     *
     * @return LengthAwarePaginator<int, StaffUser>
     */
    public function staff(string $search, int $role, string $status, int $perPage): LengthAwarePaginator
    {
        return StaffUser::query()
            ->with('roleAssignments.role')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($role > 0, fn ($query) => $query->whereHas('roleAssignments', fn ($query) => $query->where('role_id', $role)))
            ->when(in_array($status, [StaffUser::STATUS_ACTIVE, StaffUser::STATUS_INVITED, StaffUser::STATUS_LOCKED], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  list<string>  $columns
     * @return list<Role>
     */
    public function roleOptions(array $columns): array
    {
        return Role::query()->orderBy('name')->get($columns)->all();
    }
}
