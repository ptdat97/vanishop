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

    /**
     * Khai báo đúng tên; không có thì mẫu `<tiền tố>.*` dài nhất bao tên đó (điểm mở rộng tự động).
     */
    public function get(string $name): ?HookDefinition
    {
        if (isset($this->definitions[$name]) && ! $this->definitions[$name]->isPattern()) {
            return $this->definitions[$name];
        }

        $match = null;
        foreach ($this->definitions as $pattern => $definition) {
            $prefix = substr($pattern, 0, -1); // giữ dấu chấm cuối: 'vani.admin.page.'
            if ($definition->isPattern() && str_starts_with($name, $prefix) && strlen($name) > strlen($prefix)
                && ($match === null || strlen($pattern) > strlen($match->name))) {
                $match = $definition;
            }
        }

        return $match?->withName($name);
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
