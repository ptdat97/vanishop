<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Illuminate\Http\Request;
use Modules\Shared\Contracts\PluginApi;

/**
 * Đăng nhập khách vào phiên web của storefront native sau khi một phương thức đăng nhập đã cấp token (email của Core,
 * OTP của plugin `vani.phone-otp-login`, mạng xã hội…): cấp lại phiên (chống session fixation), giữ token trong phiên, gộp giỏ
 * vãng lai vào giỏ của khách. Trả URL nên chuyển tới (trang khách định vào trước khi đăng nhập, hoặc trang tài khoản).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface CustomerSignIn
{
    public function signIn(Request $request, string $token, int $customerId): string;
}
