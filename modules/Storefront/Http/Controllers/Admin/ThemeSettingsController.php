<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Theme\ThemeSettings;
use Modules\Storefront\Application\Theme\ThemeSettingSection;

/**
 * Admin → Giao diện → Đầu & chân trang: form sinh từ schema `settings` của theme đang hoạt động (§3.16-3).
 */
final class ThemeSettingsController
{
    public function edit(ThemeSettings $settings): Response
    {
        Gate::authorize('storefront.manage');

        return Inertia::render('Storefront::Design/ThemeSettings', [
            'sections' => array_values(array_map(fn (ThemeSettingSection $section): array => $section->toArray(), $settings->sections())),
            'values' => (object) $settings->stored(),
            'locales' => StoreLocale::supported(),
            'defaultLocale' => StoreLocale::default(),
        ]);
    }

    public function update(Request $request, ThemeSettings $settings, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $settings->save((array) $request->input('settings', []));
        $audit->record('storefront.theme_settings_updated', 'storefront', 'theme_settings');

        return back()->with('success', __('storefront::messages.theme_settings_saved'));
    }
}
