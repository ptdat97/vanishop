<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\AdminPathSetting;
use Modules\Identity\IdentityServiceProvider;
use Modules\Shared\Support\AdminPath;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Admin → Đường dẫn Admin: chỉ super admin. Đổi xong thì phiên hiện tại hết hiệu lực (cookie phiên gắn với đường dẫn cũ),
 * trình duyệt chuyển tới trang đăng nhập ở đường dẫn mới.
 */
final class AdminPathController
{
    public function edit(): Response
    {
        Gate::authorize(IdentityServiceProvider::SUPER_ADMIN);

        return Inertia::render('Identity::Security/AdminPath', [
            'path' => AdminPath::prefix(),
            'defaultPath' => AdminPathSetting::DEFAULT,
            'baseUrl' => rtrim(url('/'), '/').'/',
        ]);
    }

    public function update(Request $request, AdminPathSetting $setting): HttpResponse
    {
        Gate::authorize(IdentityServiceProvider::SUPER_ADMIN);
        $data = $request->validate(['path' => ['required', 'string', 'max:64']]);
        $setting->change($data['path'], (int) $request->user('staff')?->getAuthIdentifier());

        return Inertia::location(url('/'.strtolower(trim($data['path'], " /\t\n")).'/login'));
    }
}
