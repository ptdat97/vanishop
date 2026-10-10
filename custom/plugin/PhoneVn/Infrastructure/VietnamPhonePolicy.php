<?php

declare(strict_types=1);

namespace Plugin\PhoneVn\Infrastructure;

use Modules\Shared\Contracts\PhoneNumberPolicy;

/**
 * Số Việt Nam: di động 3/5/7/8/9 + 8 số, số bàn 2 + 9 số (mã vùng mới). Chấp nhận 0912345678, 84912345678,
 * +84 912 345 678, (+84) 912-345-678, 02438123456. Số nước ngoài bị từ chối (cửa hàng chỉ giao trong nước).
 */
final class VietnamPhonePolicy implements PhoneNumberPolicy
{
    public const CODE = 'vn';

    public function code(): string
    {
        return self::CODE;
    }

    public function normalize(string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if (str_starts_with($digits, '84')) {
            $national = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $national = substr($digits, 1);
        } else {
            return null;
        }

        $isMobile = preg_match('/^[35789]\d{8}$/', $national) === 1;
        $isLandline = preg_match('/^2\d{9}$/', $national) === 1;

        return $isMobile || $isLandline ? '+84'.$national : null;
    }

    public function national(string $e164): string
    {
        return str_starts_with($e164, '+84') ? '0'.substr($e164, 3) : $e164;
    }
}
