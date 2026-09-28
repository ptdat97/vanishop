<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

final readonly class DependencyProblem
{
    public function __construct(
        public string $pluginId,
        public string $code,
        public string $message,
    ) {}
}
