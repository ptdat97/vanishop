<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

/**
 * SKU mặc định khi sinh variant: {STYLE}-{giá trị trục 1}-{giá trị trục 2}…, chữ in hoa, ký tự ngoài [A-Z0-9-] thành "-".
 * "LM24-SH012" + "IVR" + "38.5" → "LM24-SH012-IVR-38-5".
 */
final class SkuPattern
{
    public const MAX_LENGTH = 64;

    public static function make(string $styleCode, string ...$valueCodes): string
    {
        $parts = array_map(
            fn (string $part): string => trim((string) preg_replace('/[^A-Z0-9]+/', '-', strtoupper($part)), '-'),
            [$styleCode, ...$valueCodes],
        );

        return substr(implode('-', array_filter($parts, fn (string $part): bool => $part !== '')), 0, self::MAX_LENGTH);
    }
}
