<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Persistence\Models\CustomerAddress;
use Modules\Shared\Domain\Phone\PhoneNumber;

/**
 * Sổ địa chỉ: tối đa 20, luôn đúng một địa chỉ mặc định khi sổ không rỗng.
 */
final class AddressBook
{
    public const MAX = 20;

    /**
     * @return list<array<string, mixed>>
     */
    public function all(int $customerId): array
    {
        return CustomerAddress::query()->where('customer_id', $customerId)->orderByDesc('is_default')->orderBy('id')->get()
            ->map(fn (CustomerAddress $address): array => $address->toView())->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function add(int $customerId, array $data): array
    {
        return DB::transaction(function () use ($customerId, $data): array {
            $count = CustomerAddress::query()->where('customer_id', $customerId)->lockForUpdate()->count();
            if ($count >= self::MAX) {
                throw CustomerRejected::addressLimit(self::MAX);
            }

            $address = CustomerAddress::query()->create([...$this->normalize($data), 'customer_id' => $customerId, 'is_default' => false]);
            if ($count === 0 || ($data['is_default'] ?? false)) {
                $this->makeDefault($customerId, $address->id);
            }

            return $address->fresh()?->toView() ?? $address->toView();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int $customerId, int $addressId, array $data): array
    {
        return DB::transaction(function () use ($customerId, $addressId, $data): array {
            $address = $this->owned($customerId, $addressId);
            $address->update($this->normalize([...$address->toView(), ...$data]));
            if ($data['is_default'] ?? false) {
                $this->makeDefault($customerId, $address->id);
            }

            return $address->fresh()?->toView() ?? $address->toView();
        });
    }

    public function delete(int $customerId, int $addressId): void
    {
        DB::transaction(function () use ($customerId, $addressId): void {
            $address = $this->owned($customerId, $addressId);
            $address->delete();

            if ($address->is_default) {
                $next = CustomerAddress::query()->where('customer_id', $customerId)->orderBy('id')->first();
                if ($next !== null) {
                    $this->makeDefault($customerId, $next->id);
                }
            }
        });
    }

    private function makeDefault(int $customerId, int $addressId): void
    {
        CustomerAddress::query()->where('customer_id', $customerId)->update(['is_default' => false]);
        CustomerAddress::query()->whereKey($addressId)->update(['is_default' => true]);
    }

    private function owned(int $customerId, int $addressId): CustomerAddress
    {
        return CustomerAddress::query()->where('customer_id', $customerId)->whereKey($addressId)->first() ?? throw CustomerRejected::notFound();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        return [
            'label' => isset($data['label']) && trim((string) $data['label']) !== '' ? trim((string) $data['label']) : null,
            'full_name' => trim((string) $data['full_name']),
            'phone' => PhoneNumber::fromString((string) $data['phone'])->e164,
            'province_code' => (string) $data['province_code'], 'province_name' => trim((string) $data['province_name']),
            'ward_code' => (string) $data['ward_code'], 'ward_name' => trim((string) $data['ward_name']),
            'street_line' => trim((string) $data['street_line']),
        ];
    }
}
