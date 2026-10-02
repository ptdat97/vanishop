<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerData;

/**
 * Service contract: đăng nhập khách cho bề mặt khác ngoài Storefront API (native storefront lưu token trong phiên).
 * Cùng quy tắc với API (ADR-024): OTP theo SĐT, token mờ, chỉ lưu băm.
 *
 * Lỗi nghiệp vụ ném CustomerRejected.
 */
interface CustomerSessions
{
    /**
     * Gửi OTP đăng nhập. `$phone` dạng người dùng nhập; trả thông tin kênh/thời gian chờ.
     *
     * @return array<string, mixed>
     */
    public function requestLoginOtp(string $phone, string $ip): array;

    /**
     * @return array{customer: CustomerData, token: string}
     */
    public function loginWithOtp(string $phone, string $code, ?string $device = null): array;

    public function authenticate(string $token): ?CustomerData;

    public function logout(string $token): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function addresses(int $customerId): array;
}
