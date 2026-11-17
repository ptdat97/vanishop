<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
enum OtpPurpose: string
{
    case Login = 'login';
    case DeleteAccount = 'delete_account';
}
