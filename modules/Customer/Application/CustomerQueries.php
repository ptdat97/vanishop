<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Shared\Domain\Phone\PhoneNumber;

/**
 * Truy vấn cho màn hình Admin khách hàng.
 */
final class CustomerQueries
{
    /**
     * @param  list<int>|null  $ids  giới hạn theo bộ lọc của plugin
     * @return LengthAwarePaginator<int, Customer>
     */
    public function search(?string $q, ?string $status, int $perPage = 30, ?array $ids = null, ?int $groupId = null, ?string $tag = null): LengthAwarePaginator
    {
        $q = trim((string) $q);
        $phone = $q === '' ? null : PhoneNumber::tryFromString($q);

        return Customer::query()
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($query) => $phone !== null
                ? $query->where('phone', $phone->e164)
                : $query->where('email', 'like', '%'.mb_strtolower($q).'%')->orWhere('full_name', 'like', "%{$q}%")->orWhere('public_id', strtoupper($q))))
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($groupId !== null, fn ($query) => $query->where('customer_group_id', $groupId))
            ->when($tag !== null && $tag !== '', fn ($query) => $query->whereIn('id', fn ($sub) => $sub->select('customer_id')->from('customer_tags')->where('tag', $tag)))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function byPublicId(string $publicId): ?Customer
    {
        return Customer::query()->where('public_id', strtoupper($publicId))->first();
    }
}
