<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class ScopeOption
{
    /**
     * "owner" → ['owner', null]; "brand:12" → ['brand', 12].
     *
     * @return array{0: string, 1: int|null}
     */
    public static function parse(string $value): array
    {
        if ($value === 'owner') {
            return ['owner', null];
        }

        if (preg_match('/^(brand|channel):(\d+)$/', $value, $matches) !== 1) {
            throw new PluginOperationFailed("Scope [{$value}] không hợp lệ. Dùng owner, brand:<id> hoặc channel:<id>.");
        }

        return [$matches[1], (int) $matches[2]];
    }
}
