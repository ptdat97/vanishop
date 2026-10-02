<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Shared\Domain\Phone\PhoneNumber;

final class CustomerSessionService implements CustomerSessions
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
        private readonly AddressBook $addresses,
    ) {}

    public function requestLoginOtp(string $phone, string $ip): array
    {
        return $this->otp->request($this->e164($phone), OtpPurpose::Login, $ip);
    }

    public function loginWithOtp(string $phone, string $code, ?string $device = null): array
    {
        $session = $this->auth->loginWithOtp($this->e164($phone), $code, $device);

        return ['customer' => $session['customer'], 'token' => $session['token']];
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

    public function addresses(int $customerId): array
    {
        return $this->addresses->all($customerId);
    }

    private function e164(string $phone): string
    {
        return PhoneNumber::tryFromString($phone)?->e164 ?? throw CustomerRejected::otpInvalid();
    }
}
