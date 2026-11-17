<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerData;

/**
 * Service contract: đăng nhập khách cho bề mặt khác ngoài Storefront API (native storefront lưu token trong phiên).
 * Cùng quy tắc với API (ADR-024): email + mật khẩu (mặc định, 0.5.12), token mờ, chỉ lưu băm. OTP theo SĐT là plugin
 * (`PhoneOtpLogin`).
 *
 * Lỗi nghiệp vụ ném CustomerRejected.
 */
interface CustomerSessions
{
    /** Khách đã nhận diện của request (middleware `vani.customer` / `vani.customer-session`): `$request->attributes->get(...)` → `CustomerData`. */
    public const REQUEST_ATTRIBUTE = 'customer';

    /**
     * Đăng ký: gửi link xác minh tới email; tài khoản chỉ được tạo khi bấm link.
     *
     * @return array{email: string, expires_in: int}
     */
    public function register(string $email, string $password, string $fullName): array;

    /**
     * Bấm link xác minh → tạo tài khoản (hoặc nhận hồ sơ khách vãng lai cùng email) và đăng nhập.
     *
     * @return array{customer: CustomerData, token: string}
     */
    public function verifyRegistration(string $token, ?string $device = null): array;

    /**
     * @return array{customer: CustomerData, token: string}
     */
    public function loginWithPassword(string $email, string $password, string $ip, ?string $device = null): array;

    /** Gửi link đặt lại mật khẩu nếu email là tài khoản; không báo email có tồn tại hay không. */
    public function requestPasswordReset(string $email): void;

    public function resetPassword(string $token, string $password): void;

    public function authenticate(string $token): ?CustomerData;

    public function logout(string $token): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function addresses(int $customerId): array;
}
