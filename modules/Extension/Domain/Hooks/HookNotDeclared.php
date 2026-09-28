<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Hooks;

use LogicException;

final class HookNotDeclared extends LogicException
{
    public function __construct(string $name)
    {
        parent::__construct("Hook [{$name}] chưa được khai báo trong hooks.php của module nào.");
    }
}
