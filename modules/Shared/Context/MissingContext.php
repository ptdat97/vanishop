<?php

declare(strict_types=1);

namespace Modules\Shared\Context;

use LogicException;

final class MissingContext extends LogicException
{
    public function __construct()
    {
        parent::__construct('Chưa có CurrentContext. Request cần middleware xác định phạm vi; job/CLI phải dùng CurrentContext::runAs().');
    }
}
