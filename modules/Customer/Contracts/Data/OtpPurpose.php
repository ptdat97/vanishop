<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

enum OtpPurpose: string
{
    case Login = 'login';
    case DeleteAccount = 'delete_account';
}
