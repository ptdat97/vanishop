<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\StoreLocale;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ngôn ngữ giao diện Admin (ADR-049), đặt trước `HandleInertiaRequests` (props dùng chung đọc ngôn ngữ lúc chia sẻ):
 * nhân viên → `staff_users.locale`; chưa chọn hoặc trang khách (đăng nhập, quên mật khẩu, lời mời) → phiên
 * `admin_locale`; không có → ngôn ngữ mặc định của cửa hàng.
 */
final class UseAdminLocale
{
    public const SESSION_KEY = 'admin_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user('staff');
        $locale = $staff instanceof StaffUser ? $staff->locale : null;
        $locale ??= $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        if (is_string($locale) && StoreLocale::isSupported($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
