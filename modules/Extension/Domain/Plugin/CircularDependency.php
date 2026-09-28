<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

use LogicException;

final class CircularDependency extends LogicException
{
    /**
     * @param  list<string>  $cycle
     */
    public function __construct(public readonly array $cycle)
    {
        parent::__construct('Phụ thuộc vòng giữa các plugin: '.implode(' → ', $cycle));
    }
}
