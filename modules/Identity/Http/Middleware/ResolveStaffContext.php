<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreLocale;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin: CurrentContext = nhân viên đang đăng nhập + locale. Ngôn ngữ Admin theo hồ sơ nhân viên (§3.3-3) nếu cửa hàng
 * hỗ trợ, không thì ngôn ngữ mặc định.
 */
final class ResolveStaffContext
{
    public function __construct(private readonly CurrentContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user('staff');

        if (! $staff instanceof StaffUser || ! $staff->isActive()) {
            Auth::guard('staff')->logout();

            return redirect()->route('admin.login');
        }

        Auth::shouldUse('staff');
        if ($staff->locale !== null && StoreLocale::isSupported($staff->locale)) {
            App::setLocale($staff->locale);
        }

        $this->context->set(new ContextScope(
            actor: Actor::staff($staff->id, $staff->email),
            locale: app()->getLocale(),
        ));

        return $next($request);
    }
}
