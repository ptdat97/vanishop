<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Phone;

use InvalidArgumentException;

final class InvalidPhoneNumber extends InvalidArgumentException
{
    public function __construct(string $input)
    {
        parent::__construct("Số điện thoại [{$input}] không hợp lệ.");
    }
}
