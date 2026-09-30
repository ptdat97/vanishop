<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Hooks;

use LogicException;

/**
 * Listener của filter trả về kiểu khác đầu vào (chỉ ném ở strict mode: local/testing; production bỏ qua kết quả).
 */
final class HookReturnTypeMismatch extends LogicException
{
    public function __construct(string $hook, ?string $pluginId, string $expected, string $actual)
    {
        parent::__construct("Filter [{$hook}] của ".($pluginId ?? 'core')." phải trả về {$expected}, nhận {$actual}.");
    }
}
