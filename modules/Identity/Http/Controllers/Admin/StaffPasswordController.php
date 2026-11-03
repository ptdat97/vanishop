<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\StaffPasswords;

/**
 * Quên mật khẩu Admin và nhận lời mời (reference-parity §3.3-4): link một lần qua email, không tiết lộ email có tồn tại,
 * giới hạn theo email + IP và theo IP.
 */
final class StaffPasswordController
{
    public function forgot(): Response
    {
        return Inertia::render('Identity::Auth/ForgotPassword', ['action' => route('admin.password.email'), 'loginUrl' => route('admin.login')]);
    }

    public function sendLink(Request $request, StaffPasswords $passwords): RedirectResponse
    {
        $email = trim((string) $request->validate(['email' => ['required', 'email', 'max:190']])['email']);

        foreach (['staff-forgot:ip:'.$request->ip() => 10, 'staff-forgot:'.sha1(mb_strtolower($email)).'|'.$request->ip() => 3] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ValidationException::withMessages(['email' => __('identity::staff.reset_throttled', ['seconds' => RateLimiter::availableIn($key)])]);
            }
            RateLimiter::hit($key, 600);
        }

        $passwords->sendReset($email);

        return back()->with('success', __('identity::staff.reset_link_sent'));
    }

    public function showReset(Request $request, string $token): Response
    {
        return $this->setPasswordPage($request, $token, route('admin.password.update'), 'Đặt lại mật khẩu');
    }

    public function reset(Request $request, StaffPasswords $passwords): RedirectResponse
    {
        return $this->complete($request, $passwords, StaffPasswords::RESET);
    }

    public function showInvitation(Request $request, string $token): Response
    {
        return $this->setPasswordPage($request, $token, route('admin.invitation.accept'), 'Đặt mật khẩu lần đầu');
    }

    public function acceptInvitation(Request $request, StaffPasswords $passwords): RedirectResponse
    {
        return $this->complete($request, $passwords, StaffPasswords::INVITE);
    }

    private function setPasswordPage(Request $request, string $token, string $action, string $title): Response
    {
        return Inertia::render('Identity::Auth/SetPassword', [
            'action' => $action,
            'title' => $title,
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    private function complete(Request $request, StaffPasswords $passwords, string $broker): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        if (! $passwords->complete($broker, (string) $data['email'], (string) $data['token'], (string) $data['password'])) {
            throw ValidationException::withMessages(['email' => __('identity::staff.link_invalid')]);
        }

        return redirect()->route('admin.login')->with('success', __('identity::staff.password_set'));
    }
}
