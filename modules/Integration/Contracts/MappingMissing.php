<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

use RuntimeException;

final class MappingMissing extends RuntimeException
{
    public function __construct(public readonly string $system, public readonly string $type, public readonly string $value)
    {
        parent::__construct("mapping.missing:{$type}:{$value}");
    }
}
