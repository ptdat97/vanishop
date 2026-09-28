<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Hooks;

use LogicException;

final class HookNotPublic extends LogicException
{
    public function __construct(string $name, string $pluginId)
    {
        parent::__construct("Plugin [{$pluginId}] không được nghe hook internal [{$name}].");
    }
}
