<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\RecordStaffLogin;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Http\Requests\StaffLoginRequest;
use Modules\Identity\Persistence\Models\StaffUser;

final class StaffSessionController
{
    private const MAX_ATTEMPTS = 5;

    public function create(): Response
    {
        return Inertia::render('Identity::Auth/Login', ['action' => route('admin.login.store')]);
    }

    public function store(StaffLoginRequest $request, RecordStaffLogin $recordLogin): RedirectResponse
    {
        $key = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('identity::auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        $credentials = [...$request->safe()->only(['email', 'password']), 'status' => 'active'];

        if (! Auth::guard('staff')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('identity::auth.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        /** @var StaffUser $staff */
        $staff = Auth::guard('staff')->user();
        $recordLogin->handle($staff, $request->ip());

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record('identity.staff.logout', 'staff_user', $request->user('staff')?->getAuthIdentifier());

        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
