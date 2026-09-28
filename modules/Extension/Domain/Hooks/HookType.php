<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Hooks;

enum HookType: string
{
    case Filter = 'filter';
    case Action = 'action';
    case Validate = 'validate';
    case Slot = 'slot';
}
