<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Http\Middleware\UseAdminLocale;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\StoreLocale;

/**
 * Dropdown ngôn ngữ Admin (ADR-049): nhân viên đã đăng nhập lưu vào hồ sơ (`staff_users.locale`, cùng ô ở trang Hồ sơ);
 * trang khách (đăng nhập, quên mật khẩu, lời mời) lưu vào phiên. Trang tải lại theo ngôn ngữ mới.
 */
final class AdminLocaleController
{
    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(StoreLocale::supported())]]);
        $request->session()->put(UseAdminLocale::SESSION_KEY, $data['locale']);

        $staff = $request->user('staff');
        if ($staff instanceof StaffUser && $staff->locale !== $data['locale']) {
            $staff->update(['locale' => $data['locale']]);
            $audit->record('identity.staff.profile_updated', 'staff_user', $staff->id, ['locale' => $staff->locale]);
        }

        return back();
    }
}
