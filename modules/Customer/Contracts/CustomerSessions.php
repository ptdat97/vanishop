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
    /** Khách đã nhận diện của request (middleware `vani.customer` / `vani.customer-session`): `$request->attributes->get(...)` → model khách (đọc `id`; cần dữ liệu khách thì `Customers::find($id)` → `CustomerData`), null = chưa đăng nhập. */
    public const REQUEST_ATTRIBUTE = 'customer';

    /**
     * Đăng ký: gửi link xác minh tới email; tài khoản chỉ được tạo khi bấm link. `$marketingEmail` = khách tích "Nhận ưu đãi
     * qua email"; đồng ý chỉ được ghi khi xác minh xong.
     *
     * @return array{email: string, expires_in: int}
     */
    public function register(string $email, string $password, string $fullName, bool $marketingEmail = false): array;

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
     * Gửi link xác minh email hiện tại của tài khoản (0.5.32 — đổi/thêm email trong hồ sơ, hoặc khách bấm "Gửi lại").
     * Không có email hoặc đã xác minh → false (không gửi). Gửi quá nhiều tới một địa chỉ → `customer.too_many_attempts`.
     */
    public function sendEmailVerification(int $customerId): bool;

    /**
     * Bấm link xác minh email: đánh dấu đã xác minh nếu email của tài khoản vẫn là email đã gửi link. Link một lần;
     * sai/hết hạn/email đã đổi tiếp → null.
     */
    public function verifyEmail(string $token): ?CustomerData;

    /**
     * @return list<array<string, mixed>>
     */
    public function addresses(int $customerId): array;
}
