<?php

declare(strict_types=1);

namespace Modules\Customer\Domain;

/**
 * OTP 6 số (docs/03-domains/customer.md §4). Hiệu lực và số lần thử: cấu hình `vanishop.customer.otp.ttl|max_attempts`.
 */
final class OtpCode
{
    public const LENGTH = 6;

    public static function generate(): string
    {
        return str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * HMAC với khoá bí mật của ứng dụng: lộ bảng OTP không đủ để dò mã 6 số.
     */
    public static function hash(string $secret, string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone.'|'.$code, $secret);
    }

    public static function isWellFormed(string $code): bool
    {
        return preg_match('/^\d{'.self::LENGTH.'}$/', $code) === 1;
    }
}
