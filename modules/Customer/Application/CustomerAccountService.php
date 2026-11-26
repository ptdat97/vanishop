<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\URL;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Persistence\Models\Customer;

final class CustomerAccountService implements CustomerAccounts
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly AuthService $auth,
        private readonly AddressBook $addresses,
        private readonly AccountLifecycle $lifecycle,
        private readonly ConsentService $consents,
    ) {}

    public function updateProfile(int $customerId, array $attributes): CustomerData
    {
        $attributes = vani_filter('vani.service.customer.profile_attributes', $attributes, $customerId);

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

    public function deletionBlockers(int $customerId): array
    {
        return $this->lifecycle->deletionBlockers($customerId);
    }

    public function requestDeletionCode(int $customerId, string $ip): array
    {
        return $this->lifecycle->requestDeletionCode($customerId, $ip);
    }

    public function deleteAccount(int $customerId, ?string $password = null, ?string $code = null): void
    {
        $this->lifecycle->deleteOwnAccount($customerId, $password, $code);
    }

    public function marketingConsents(int $customerId): array
    {
        return $this->consents->all($customerId);
    }

    public function marketingConsent(int $customerId, string $channel): bool
    {
        return $this->consents->allows($customerId, $channel, 'marketing');
    }

    public function setMarketingConsent(int $customerId, string $channel, bool $granted, string $source, ?string $ip = null): void
    {
        $this->consents->set($customerId, $channel, 'marketing', $granted, $source, $ip);
    }

    public function unsubscribeUrl(int $customerId, string $channel): string
    {
        $publicId = (string) Customer::query()->whereKey($customerId)->value('public_id');

        return URL::signedRoute('storefront.account.unsubscribe', ['customer' => $publicId, 'channel' => $channel]);
    }

    public function unsubscribe(string $customerPublicId, string $channel, ?string $ip = null): bool
    {
        $customerId = Customer::query()->where('public_id', $customerPublicId)->value('id');
        if ($customerId === null) {
            return false;
        }
        $this->consents->set((int) $customerId, $channel, 'marketing', false, 'unsubscribe_link', $ip);

        return true;
    }
}
