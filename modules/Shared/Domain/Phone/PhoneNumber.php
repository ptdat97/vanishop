<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Phone;

/**
 * Số điện thoại Việt Nam, chuẩn hoá về E.164 (+84…).
 *
 * Chấp nhận: 0912345678, 84912345678, +84 912 345 678, (+84) 912-345-678, 02438123456.
 */
final readonly class PhoneNumber
{
    private function __construct(public string $e164) {}

    public static function fromString(string $input): self
    {
        return self::tryFromString($input) ?? throw new InvalidPhoneNumber($input);
    }

    public static function tryFromString(string $input): ?self
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

        return $isMobile || $isLandline ? new self('+84'.$national) : null;
    }

    /**
     * Dạng hiển thị trong nước: 0912345678.
     */
    public function national(): string
    {
        return '0'.substr($this->e164, 3);
    }

    /**
     * Dạng che cho log/danh sách: 091****678.
     */
    public function masked(): string
    {
        $national = $this->national();

        return substr($national, 0, 3).str_repeat('*', strlen($national) - 6).substr($national, -3);
    }

    public function equals(self $other): bool
    {
        return $this->e164 === $other->e164;
    }
}
