<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Delivery;

use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Connector đang ngắt mạch: message được hoãn tới `$retryAt`, không tính là một lần thử.
 */
final class CircuitOpen extends RuntimeException
{
    public function __construct(public readonly string $system, public readonly CarbonImmutable $retryAt)
    {
        parent::__construct("circuit.open:{$system}");
    }
}
