<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Flows;

use Modules\Extension\Contracts\Flows;

final class FlowRegistry implements Flows
{
    /** @var array<string, array{label: string, steps: callable}> */
    private array $flows = [];

    public function describe(string $code, string $label, callable $steps): void
    {
        $this->flows[$code] = ['label' => $label, 'steps' => $steps];
    }

    public function all(): array
    {
        ksort($this->flows);

        return $this->flows;
    }
}
