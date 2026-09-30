<?php

declare(strict_types=1);

namespace Modules\Integration\Domain;

/**
 * Che PII trước khi hiển thị payload trên màn hình vận hành (Integration Health).
 */
final class PayloadMasker
{
    private const SENSITIVE = ['phone', 'email', 'full_name', 'street_line', 'address', 'name_on_card', 'tax_code', 'id_number'];

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function mask(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::mask($value);
            } elseif (is_string($key) && in_array($key, self::SENSITIVE, true) && is_scalar($value) && (string) $value !== '') {
                $payload[$key] = self::maskValue((string) $value);
            }
        }

        return $payload;
    }

    private static function maskValue(string $value): string
    {
        $length = mb_strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, 2).str_repeat('*', $length - 4).mb_substr($value, -2);
    }
}
