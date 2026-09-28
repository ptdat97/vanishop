<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks;

use Modules\Extension\Domain\Hooks\HookDefinition;

/**
 * Danh sách hook đã khai báo (hooks.php của module Core).
 */
final class HookRegistry
{
    /** @var array<string, HookDefinition> */
    private array $definitions = [];

    public function declare(HookDefinition $definition): void
    {
        $this->definitions[$definition->name] = $definition;
    }

    /**
     * @param  array<string, array{type: string, visibility?: string, since?: string, args?: array<string, string>, description?: string}>  $definitions
     */
    public function declareMany(array $definitions): void
    {
        foreach ($definitions as $name => $definition) {
            $this->declare(HookDefinition::fromArray($name, $definition));
        }
    }

    public function get(string $name): ?HookDefinition
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * @return array<string, HookDefinition>
     */
    public function all(): array
    {
        ksort($this->definitions);

        return $this->definitions;
    }
}
