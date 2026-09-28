<?php

declare(strict_types=1);

namespace Modules\Extension\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Extension\Application\Hooks\HookManager;

/**
 * @method static mixed filter(string $name, mixed $value, mixed ...$args)
 * @method static void action(string $name, mixed ...$args)
 * @method static array collect(string $name, mixed ...$args)
 * @method static array slot(string $name, mixed ...$args)
 * @method static void onFilter(string $name, callable $callback, int $priority = 10, ?string $pluginId = null)
 * @method static void onAction(string $name, callable $callback, int $priority = 10, ?string $pluginId = null)
 * @method static void onValidate(string $name, callable $callback, int $priority = 10, ?string $pluginId = null)
 * @method static void onSlot(string $name, callable $callback, int $priority = 10, ?string $pluginId = null)
 *
 * @see HookManager
 */
final class Hook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
