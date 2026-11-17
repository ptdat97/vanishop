<?php

declare(strict_types=1);

namespace Plugin\PhoneOtpLogin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Contracts\PhoneOtpLogin;

/**
 * Storefront API của plugin: gửi mã OTP tới SĐT, đổi mã lấy token Bearer (trả một lần trong `meta.token`, ADR-024). Lỗi
 * nghiệp vụ theo error.v1 của Core (`customer.otp_invalid`, `customer.otp_rate_limited`, `customer.phone_invalid`…).
 */
final class OtpLoginApiController
{
    public function __construct(private readonly PhoneOtpLogin $otp) {}

    public function request(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);

        return response()->json(['data' => $this->otp->requestCode($data['phone'], (string) $request->ip())], 202);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:10'],
            'device' => ['nullable', 'string', 'max:64'],
        ]);
        $session = $this->otp->login($data['phone'], $data['code'], $data['device'] ?? null);

        return response()->json(['data' => $session['customer']->toArray(), 'meta' => ['token' => $session['token'], 'token_type' => 'Bearer']]);
    }
}
