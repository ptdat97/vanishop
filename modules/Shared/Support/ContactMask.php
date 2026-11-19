<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Che thông tin liên hệ của khách cho người không có quyền xem (`customers.pii`) và trang tra cứu đơn của khách:
 *
 * "Nguyễn Thị Lan" → "N. T. Lan", "0912345678" → "091****678", email → "l***@example.com", số nhà/đường → "***".
 */
final class ContactMask
{
    public const HIDDEN = '***';

    public static function name(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];
        $last = array_pop($parts) ?? '';

        return trim(implode(' ', array_map(fn (string $part): string => mb_substr($part, 0, 1).'.', $parts)).' '.$last);
    }

    public static function phone(?string $phone): string
    {
        return Phones::parse((string) $phone)?->masked() ?? ($phone === null || $phone === '' ? '' : self::HIDDEN);
    }

    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '' || ! str_contains($email, '@')) {
            return $email === null || $email === '' ? null : self::HIDDEN;
        }
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
