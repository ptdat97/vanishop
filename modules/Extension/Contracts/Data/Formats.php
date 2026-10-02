<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

use InvalidArgumentException;

/**
 * @internal Định dạng giá trị mà Admin hiển thị cho Metric/Series/Column.
 */
final class Formats
{
    public const NUMERIC = ['number', 'money', 'percent'];

    public static function guard(string $format, bool $allowText = false): void
    {
        if (! in_array($format, $allowText ? [...self::NUMERIC, 'text'] : self::NUMERIC, true)) {
            throw new InvalidArgumentException("Định dạng [{$format}] không hợp lệ.");
        }
    }
}
