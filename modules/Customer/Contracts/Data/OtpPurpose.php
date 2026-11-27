<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
enum OtpPurpose: string
{
    case Login = 'login';
    case DeleteAccount = 'delete_account';
    /** Xác minh SĐT khi đăng ký (0.5.114, cài đặt `customer.registration_verification` = otp). */
    case Register = 'register';
}
