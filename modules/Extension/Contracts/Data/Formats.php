<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

use InvalidArgumentException;

/**
 * @internal Định dạng giá trị mà Admin hiển thị cho Metric/Series/Column.
 */
final class Formats
{
    /** `compact`/`money_compact` (0.3.73): số rút gọn nghìn/triệu/tỷ cho dashboard — 1,3 N · 2,4 Tr ₫. */
    public const NUMERIC = ['number', 'money', 'percent', 'compact', 'money_compact'];

    public static function guard(string $format, bool $allowText = false): void
    {
        if (! in_array($format, $allowText ? [...self::NUMERIC, 'text'] : self::NUMERIC, true)) {
            throw new InvalidArgumentException("Định dạng [{$format}] không hợp lệ.");
        }
    }
}
