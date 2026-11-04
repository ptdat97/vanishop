<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\StoreLocale;

/**
 * Admin → Hồ sơ của tôi (reference-parity §3.3-3): tên, ngôn ngữ Admin riêng, đổi mật khẩu (cần mật khẩu hiện tại).
 * Email và vai trò do nhân viên quản trị đổi ở Admin → Nhân viên.
 */
final class ProfileController
{
    public function edit(Request $request): Response
    {
        $staff = $this->staff($request);

        return Inertia::render('Identity::Profile', [
            'profile' => ['name' => $staff->name, 'email' => $staff->email, 'locale' => $staff->locale],
            'locales' => StoreLocale::options(),
            'defaultLocale' => StoreLocale::default(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'locale' => ['nullable', 'string', Rule::in(StoreLocale::supported())],
        ]);
        $staff->update(['name' => trim($data['name']), 'locale' => $data['locale'] ?? null]);
        $audit->record('identity.staff.profile_updated', 'staff_user', $staff->id, ['locale' => $staff->locale]);

        return back()->with('success', __('identity::staff.profile_saved', [], $staff->locale ?? StoreLocale::default()));
    }

    public function updatePassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);
        if (! Hash::check($data['current_password'], (string) $staff->password)) {
            throw ValidationException::withMessages(['current_password' => __('identity::staff.current_password_wrong')]);
        }

        // Đổi remember token: "ghi nhớ đăng nhập" ở máy khác hết hiệu lực; phiên này giữ nguyên.
        $staff->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $audit->record('identity.staff.password_changed', 'staff_user', $staff->id);

        return back()->with('success', __('identity::staff.password_changed'));
    }

    private function staff(Request $request): StaffUser
    {
        $staff = $request->user('staff');
        abort_unless($staff instanceof StaffUser, 403);

        return $staff;
    }
}
