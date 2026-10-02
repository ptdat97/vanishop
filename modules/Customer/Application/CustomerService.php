<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Shared\Domain\Phone\PhoneNumber;

final class CustomerService implements Customers
{
    public function find(int $customerId): ?CustomerData
    {
        $customer = Customer::query()->find($customerId);

        return $customer === null ? null : self::toData($customer);
    }

    public function hasConsent(int $customerId, string $channel, string $purpose): bool
    {
        return app(ConsentService::class)->allows($customerId, $channel, $purpose);
    }

    public function resolveForCheckout(string $phone, string $fullName, ?string $email): int
    {
        $customer = $this->findOrCreateByPhone(PhoneNumber::fromString($phone)->e164, $fullName);

        $changes = [];
        if (($customer->full_name ?? '') === '' && trim($fullName) !== '') {
            $changes['full_name'] = trim($fullName);
        }
        $email = $email === null ? '' : mb_strtolower(trim($email));
        if ($customer->email === null && $email !== '' && ! $this->emailInUse($email, $customer->id)) {
            $changes['email'] = $email;
        }
        if ($changes !== []) {
            $customer->update($changes);
        }

        return $customer->id;
    }

    /**
     * Khách đang hoạt động có SĐT này; chưa có thì tạo profile ẩn. An toàn khi hai request cùng tạo (unique phone_active).
     */
    public function findOrCreateByPhone(string $e164, ?string $fullName = null): Customer
    {
        $existing = $this->activeByPhone($e164);
        if ($existing !== null) {
            return $existing;
        }

        try {
            // Savepoint riêng: lỗi trùng khoá không làm hỏng transaction bên ngoài. Gọi ngoài transaction (checkout):
            // tự thử lại khi deadlock do nhiều request cùng chèn một SĐT.
            return DB::transaction(fn (): Customer => Customer::query()->create([
                'public_id' => (string) Str::ulid(),
                'phone' => $e164,
                'full_name' => $fullName === null || trim($fullName) === '' ? null : trim($fullName),
                'status' => CustomerStatus::Active,
            ]), 3);
        } catch (UniqueConstraintViolationException) {
            return $this->activeByPhone($e164) ?? throw CustomerRejected::notFound();
        }
    }

    public function activeByPhone(string $e164): ?Customer
    {
        return Customer::query()->where('phone_active', $e164)->first();
    }

    public function emailInUse(string $email, ?int $exceptId = null): bool
    {
        return Customer::query()->where('email_normalized', mb_strtolower(trim($email)))
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))->exists();
    }

    /**
     * @param  array{full_name?: ?string, email?: ?string, birth_date?: ?string, gender?: ?string}  $attributes
     */
    public function updateProfile(int $customerId, array $attributes): CustomerData
    {
        $customer = Customer::query()->findOrFail($customerId);
        if (array_key_exists('email', $attributes)) {
            $email = $attributes['email'] === null ? null : mb_strtolower(trim($attributes['email']));
            if ($email !== null && $email !== '' && $this->emailInUse($email, $customer->id)) {
                throw CustomerRejected::emailTaken();
            }
            $attributes['email'] = $email === '' ? null : $email;
        }

        try {
            $customer->update($attributes);
        } catch (UniqueConstraintViolationException) {
            throw CustomerRejected::emailTaken();
        }

        return self::toData($customer->fresh() ?? $customer);
    }

    public static function toData(Customer $customer): CustomerData
    {
        return new CustomerData(
            $customer->id, $customer->public_id, $customer->phone, $customer->email, $customer->full_name, $customer->status->value,
            $customer->isRegistered(), $customer->birth_date?->format('Y-m-d'), $customer->gender, $customer->password !== null,
        );
    }
}
