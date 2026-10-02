<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Admin\DashboardWidgets;
use Modules\Extension\Facades\Hook;

/**
 * Trang tổng quan Admin (khung ứng dụng). Plugin đóng góp widget có kiểu (DashboardWidget, W6b, vd. `vani.reports`);
 * slot vani.admin.dashboard.cards (card chữ) vẫn giữ.
 */
final class DashboardController
{
    public function __invoke(DashboardWidgets $widgets): Response
    {
        Gate::authorize('admin.access');

        return Inertia::render('Dashboard', [
            'widgets' => $widgets->visible(),
            'cards' => Hook::slot('vani.admin.dashboard.cards'),
        ]);
    }
}
