<?php

declare(strict_types=1);

namespace Modules\Customer\Domain;

/**
 * OTP 6 số, TTL 5 phút, tối đa 5 lần thử (docs/03-domains/customer.md §4).
 */
final class OtpCode
{
    public const LENGTH = 6;

    public const TTL_SECONDS = 300;

    public const MAX_ATTEMPTS = 5;

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
