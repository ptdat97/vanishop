<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Inertia cho Admin (chỉ gắn vào route Admin; storefront dùng Blade).
 */
final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'admin';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $staff = $request->user('staff');

        return [
            ...parent::share($request),
            'app' => ['name' => config('app.name'), 'locale' => app()->getLocale()],
            'auth' => [
                'staff' => $staff instanceof StaffUser ? ['id' => $staff->id, 'name' => $staff->name, 'email' => $staff->email] : null,
            ],
            'navigation' => fn (): array => $staff instanceof StaffUser ? app(AdminNavigation::class)->visibleItems() : [],
            'urls' => fn (): array => $staff instanceof StaffUser ? ['logout' => route('admin.logout')] : [],
            'flash' => fn (): array => ['success' => $request->session()->get('success')],
        ];
    }
}
