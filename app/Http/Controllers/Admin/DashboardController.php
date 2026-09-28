<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Facades\Hook;

/**
 * Trang tổng quan Admin (khung ứng dụng). Module/plugin thêm card qua slot vani.admin.dashboard.cards.
 */
final class DashboardController
{
    public function __invoke(): Response
    {
        Gate::authorize('admin.access');

        return Inertia::render('Dashboard', [
            'cards' => Hook::slot('vani.admin.dashboard.cards'),
        ]);
    }
}
