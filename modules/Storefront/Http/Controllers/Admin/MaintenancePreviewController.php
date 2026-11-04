<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\Maintenance;

/**
 * Nhân viên xem cửa hàng khi đang bảo trì: phát cookie có chữ ký (path `/`) cho trình duyệt này rồi về trang chủ.
 */
final class MaintenancePreviewController
{
    public function __invoke(Maintenance $maintenance, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $audit->record('storefront.maintenance_preview', 'storefront', 'maintenance', ['hours' => Maintenance::BYPASS_HOURS]);

        return redirect()->to(LocalizedUrls::route('storefront.home'))
            ->withCookie($maintenance->bypassCookie());
    }
}
