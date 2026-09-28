<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Đường dẫn gốc của Admin, cấu hình qua VANI_ADMIN_PATH (ADR-020).
 */
final class AdminPath
{
    public static function prefix(): string
    {
        $path = trim((string) config('vanishop.admin.path', 'admin'), '/');

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $path) !== 1) {
            throw new InvalidArgumentException("VANI_ADMIN_PATH [{$path}] không hợp lệ: chỉ dùng chữ thường, số và gạch ngang, không có dấu /.");
        }

        if (in_array($path, (array) config('vanishop.reserved_paths', []), true)) {
            throw new InvalidArgumentException("VANI_ADMIN_PATH [{$path}] trùng đường dẫn dành riêng.");
        }

        return $path;
    }

    public static function matches(Request $request): bool
    {
        $prefix = self::prefix();

        return $request->is($prefix, $prefix.'/*');
    }

    /**
     * Mọi đường dẫn gốc dành riêng, gồm cả đường dẫn Admin.
     *
     * @return list<string>
     */
    public static function reservedPaths(): array
    {
        return array_values(array_unique([...(array) config('vanishop.reserved_paths', []), self::prefix()]));
    }
}
