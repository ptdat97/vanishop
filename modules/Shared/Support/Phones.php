<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Modules\Shared\Contracts\PhoneNumberPolicy;
use Modules\Shared\Domain\Phone\InvalidPhoneNumber;
use Modules\Shared\Domain\Phone\PhoneNumber;

/**
 * Đọc số điện thoại người dùng nhập theo luật thị trường của cửa hàng (PhoneNumberPolicy đang chọn — composition root
 * bind theo `vanishop.locale.phone_policy`).
 */
final class Phones
{
    public static function parse(string $input): ?PhoneNumber
    {
        $policy = app(PhoneNumberPolicy::class);
        $e164 = $policy->normalize($input);

        return $e164 === null ? null : PhoneNumber::of($e164, $policy->national($e164));
    }

    /**
     * @throws InvalidPhoneNumber
     */
    public static function fromString(string $input): PhoneNumber
    {
        return self::parse($input) ?? throw new InvalidPhoneNumber($input);
    }
}
