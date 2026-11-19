<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Settings;

use Modules\Extension\Contracts\SettingActions;
use Modules\Tenancy\Contracts\Data\SettingGroup;

final class SettingActionRegistry implements SettingActions
{
    /** @var array<string, list<array{label: string, route: string}>> */
    private array $actions = [];

    public function add(SettingGroup $group, string $label, string $route): void
    {
        $this->actions[$group->value][] = ['label' => $label, 'route' => $route];
    }

    public function for(SettingGroup $group): array
    {
        return array_map(fn (array $action): array => ['label' => (string) __($action['label']), 'url' => route($action['route'])], $this->actions[$group->value] ?? []);
    }
}
