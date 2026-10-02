<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin: CurrentContext = nhân viên đang đăng nhập + locale.
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

        $this->context->set(new ContextScope(
            actor: Actor::staff($staff->id, $staff->email),
            locale: app()->getLocale(),
        ));

        return $next($request);
    }
}
