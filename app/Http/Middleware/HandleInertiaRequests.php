<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Middleware;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreClock;
use Modules\Shared\Support\StoreLocale;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia cho Admin (chỉ gắn vào route Admin; storefront dùng Blade).
 */
final class HandleInertiaRequests extends Middleware
{
    /** Khoá session của thông báo (theo tone của toast). */
    public const TOAST_TONES = ['success', 'warning', 'error', 'info'];

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
            // locales: ngôn ngữ bản dịch của cửa hàng (mặc định đứng đầu, bắt buộc có bản dịch).
            'app' => [
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
                'locales' => StoreLocale::options(),
                'defaultLocale' => StoreLocale::default(),
                // Định dạng tiền của cửa hàng cho MoneyText — cùng quy tắc với MoneyFormatter ở server.
                'money' => app(MoneyFormatter::class)->rules((string) config('vanishop.currency'), app()->getLocale()),
                // Múi giờ + mẫu ngày giờ (PHP date()) của cửa hàng cho useDateTime — cùng quy tắc với StoreClock::format().
                'clock' => ['timezone' => StoreClock::timezone(), 'formats' => (array) config('vanishop.locale.formats')],
            ],
            'auth' => [
                'staff' => $staff instanceof StaffUser ? ['id' => $staff->id, 'name' => $staff->name, 'email' => $staff->email] : null,
            ],
            'navigation' => fn (): array => $staff instanceof StaffUser ? app(AdminNavigation::class)->visibleItems() : [],
            // Nhóm accordion của sidebar (chỉ nhóm có mục hiển thị).
            'navigationGroups' => fn (): array => $staff instanceof StaffUser ? app(AdminNavigation::class)->visibleGroups() : [],
            // media: Thư viện ảnh cho modal chọn ảnh (MediaPicker); null khi nhân viên không có quyền media.view.
            'urls' => fn (): array => $staff instanceof StaffUser ? [
                'logout' => route('admin.logout'),
                'profile' => route('admin.profile.edit'),
                'media' => Gate::forUser($staff)->allows('media.view') ? route('admin.media.index') : null,
            ] : [],
        ];
    }

    /**
     * Thông báo cho Toaster: `->with('success'|'warning'|'error'|'info', …)` trong session → flash data của Inertia `toasts`
     * (`page.flash`, sự kiện `flash` phát ở **mọi** phản hồi mang flash — mỗi thao tác một toast; không lưu vào lịch sử trình
     * duyệt nên quay lại trang không hiện lại thông báo cũ).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $toasts = [];
        foreach (self::TOAST_TONES as $tone) {
            $message = $request->hasSession() ? $request->session()->get($tone) : null;
            if (is_string($message) && $message !== '') {
                $toasts[] = ['type' => $tone, 'message' => $message];
            }
        }
        if ($toasts !== []) {
            Inertia::flash('toasts', $toasts);
        }

        return parent::handle($request, $next);
    }
}
