<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application;

use Modules\Tenancy\Contracts\Data\SettingDefinition;

/**
 * Danh mục định nghĩa cấu hình (singleton): module Core và plugin khai báo lúc boot.
 */
final class SettingDefinitions
{
    /** @var array<string, array<string, SettingDefinition>> namespace => key => definition */
    private array $definitions = [];

    public function add(SettingDefinition $definition): void
    {
        $this->definitions[$definition->namespace][$definition->key] = $definition;
    }

    public function find(string $namespace, string $key): ?SettingDefinition
    {
        return $this->definitions[$namespace][$key] ?? null;
    }

    /**
     * @return list<SettingDefinition>
     */
    public function all(?string $namespace = null): array
    {
        if ($namespace !== null) {
            return array_values($this->definitions[$namespace] ?? []);
        }

        $all = [];
        foreach ($this->definitions as $definitions) {
            array_push($all, ...array_values($definitions));
        }

        return $all;
    }
}
