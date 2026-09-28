<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Money;

use InvalidArgumentException;

final class CurrencyMismatch extends InvalidArgumentException
{
    public function __construct(Currency $left, Currency $right)
    {
        parent::__construct("Không thể tính toán giữa {$left->code} và {$right->code}.");
    }
}
