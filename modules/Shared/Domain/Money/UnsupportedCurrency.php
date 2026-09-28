<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Money;

use InvalidArgumentException;

final class UnsupportedCurrency extends InvalidArgumentException
{
    public function __construct(string $code)
    {
        parent::__construct("Tiền tệ [{$code}] chưa được hỗ trợ.");
    }
}
