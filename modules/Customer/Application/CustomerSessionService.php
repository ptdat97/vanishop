<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;

final class CustomerSessionService implements CustomerSessions
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AddressBook $addresses,
    ) {}

    public function register(string $email, string $password, string $fullName, string $phone, bool $marketingEmail = false, string $ip = '', ?string $device = null): array
    {
        return $this->auth->register($email, $password, $fullName, $phone, $marketingEmail, $ip, $device);
    }

    public function verifyRegistrationOtp(string $phone, string $code, ?string $device = null): array
    {
        $session = $this->auth->verifyRegistrationOtp($phone, $code, $device);

        return ['customer' => $session['customer'], 'token' => $session['token']];
    }

    public function verifyRegistration(string $token, ?string $device = null): array
    {
        $session = $this->auth->verifyRegistration($token, $device);

        return ['customer' => $session['customer'], 'token' => $session['token']];
    }

    public function loginWithPassword(string $email, string $password, string $ip, ?string $device = null): array
    {
        $session = $this->auth->loginWithPassword($email, $password, $ip, $device);

        return ['customer' => $session['customer'], 'token' => $session['token']];
    }

    public function requestPasswordReset(string $email): void
    {
        $this->auth->requestPasswordReset($email);
    }

    public function resetPassword(string $token, string $password): void
    {
        $this->auth->resetPassword($token, $password);
    }

    public function authenticate(string $token): ?CustomerData
    {
        $customer = $this->auth->authenticate($token);

        return $customer === null ? null : CustomerService::toData($customer);
    }

    public function logout(string $token): void
    {
        $this->auth->logout($token);
    }

    public function sendEmailVerification(int $customerId): bool
    {
        return $this->auth->sendEmailVerification($customerId);
    }

    public function verifyEmail(string $token): ?CustomerData
    {
        return $this->auth->verifyEmail($token);
    }

    public function addresses(int $customerId): array
    {
        return $this->addresses->all($customerId);
    }
}
