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
     * @return LengthAwarePaginator<int, Customer>
     */
    public function search(?string $q, ?string $status, int $perPage = 30): LengthAwarePaginator
    {
        $q = trim((string) $q);
        $phone = $q === '' ? null : PhoneNumber::tryFromString($q);

        return Customer::query()
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($query) => $phone !== null
                ? $query->where('phone', $phone->e164)
                : $query->where('email', 'like', '%'.mb_strtolower($q).'%')->orWhere('full_name', 'like', "%{$q}%")->orWhere('public_id', strtoupper($q))))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function byPublicId(string $publicId): ?Customer
    {
        return Customer::query()->where('public_id', strtoupper($publicId))->first();
    }
}
