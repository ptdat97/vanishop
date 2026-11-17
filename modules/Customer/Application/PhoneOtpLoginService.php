<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\PhoneOtpLogin;
use Modules\Shared\Support\Phones;

/**
 * Đăng nhập OTP theo SĐT cho plugin (`vani.phone-otp-login`): khung OTP + quy tắc tài khoản của Core.
 */
final class PhoneOtpLoginService implements PhoneOtpLogin
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
    ) {}

    public function requestCode(string $phone, string $ip): array
    {
        return $this->otp->request($this->e164($phone), OtpPurpose::Login, $ip);
    }

    public function login(string $phone, string $code, ?string $device = null): array
    {
        $session = $this->auth->loginWithOtp($this->e164($phone), $code, $device);

        return ['customer' => $session['customer'], 'token' => $session['token']];
    }

    private function e164(string $phone): string
    {
        return Phones::parse($phone)->e164 ?? throw CustomerRejected::phoneInvalid();
    }
}
