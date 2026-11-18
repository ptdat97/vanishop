<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Database\Eloquent\Collection;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerGroup;

/**
 * Truy vấn đọc cho màn hình Admin của Customer — controller không tự truy vấn (R9).
 */
final class AdminQueries
{
    /**
     * @return Collection<int, CustomerGroup>
     */
    public function groupsWithUsage(): Collection
    {
        return CustomerGroup::query()->withCount('customers')->orderBy('position')->orderBy('name')->get();
    }

    public function group(int $id): CustomerGroup
    {
        return CustomerGroup::query()->findOrFail($id);
    }

    public function publicId(int $customerId): ?string
    {
        $publicId = Customer::query()->whereKey($customerId)->value('public_id');

        return $publicId === null ? null : (string) $publicId;
    }
}
