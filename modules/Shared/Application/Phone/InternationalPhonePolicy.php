<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Phone;

use Modules\Shared\Contracts\PhoneNumberPolicy;

/**
 * Mặc định trung lập của Core: chỉ nhận số đã ở dạng quốc tế (`+84 912 345 678`, `0084912345678`); không đoán mã quốc
 * gia cho số trong nước — đó là luật của thị trường (plugin, vd. `vani.phone-vn`).
 */
final class InternationalPhonePolicy implements PhoneNumberPolicy
{
    public const CODE = 'international';

    public function code(): string
    {
        return self::CODE;
    }

    public function normalize(string $input): ?string
    {
        $compact = preg_replace('/[\s().\-]+/', '', trim($input)) ?? '';
        if (str_starts_with($compact, '00')) {
            $compact = '+'.substr($compact, 2);
        }

        return preg_match('/^\+[1-9]\d{6,14}$/', $compact) === 1 ? $compact : null;
    }

    public function national(string $e164): string
    {
        return $e164;
    }
}
