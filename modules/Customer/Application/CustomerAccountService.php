<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\Data\CustomerData;

final class CustomerAccountService implements CustomerAccounts
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly AuthService $auth,
        private readonly AddressBook $addresses,
    ) {}

    public function updateProfile(int $customerId, array $attributes): CustomerData
    {
        return $this->customers->updateProfile($customerId, array_intersect_key($attributes, array_flip(['full_name', 'email', 'birth_date', 'gender'])));
    }

    public function setPassword(int $customerId, ?string $current, string $new): void
    {
        $this->auth->setPassword($customerId, $current, $new);
    }

    public function address(int $customerId, int $addressId): ?array
    {
        foreach ($this->addresses->all($customerId) as $address) {
            if ($address['id'] === $addressId) {
                return $address;
            }
        }

        return null;
    }

    public function addAddress(int $customerId, array $data): array
    {
        return $this->addresses->add($customerId, $data);
    }

    public function updateAddress(int $customerId, int $addressId, array $data): array
    {
        return $this->addresses->update($customerId, $addressId, $data);
    }

    public function deleteAddress(int $customerId, int $addressId): void
    {
        $this->addresses->delete($customerId, $addressId);
    }
}
