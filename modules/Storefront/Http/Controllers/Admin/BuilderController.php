<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Application\Maintenance;
use Modules\Storefront\Application\PageBlocks;

/**
 * Admin → Giao diện → Trang chủ: kéo thả block của trang chủ, cấu hình theo fields() của từng loại block (Core + plugin).
 * Trang khác không có Builder; nội dung thêm ở đó do plugin/theme chèn bằng code tại slot.
 */
final class BuilderController
{
    public function home(): RedirectResponse
    {
        return redirect()->route('admin.storefront.builder.edit');
    }

    public function edit(BuilderEngine $engine, PageBlocks $blocks, Maintenance $maintenance): Response
    {
        Gate::authorize('storefront.manage');
        $stored = $engine->blocks();

        return Inertia::render('Storefront::Design/Builder', [
            'types' => $blocks->editorTypes(),
            'blocks' => $blocks->editorBlocks($stored ?? []),
            'draftUrls' => [
                'preview' => route('admin.storefront.builder.draft', 'preview'),
                'import' => route('admin.storefront.builder.draft', 'import'),
            ],
            'configured' => $stored !== null,
            // Đang bảo trì: đi qua route phát cookie xem trước, không thì nhân viên chỉ thấy trang 503.
            'previewUrl' => $maintenance->enabled() ? route('admin.storefront.maintenance.preview') : route('storefront.home'),
        ]);
    }

    public function update(Request $request, BuilderEngine $engine, PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $data = $request->validate($blocks->inputRules());
        $clean = $engine->save($data['blocks']);
        $audit->record('storefront.builder_updated', 'storefront', BuilderEngine::HOME, ['count' => count($clean)]);

        return back()->with('success', __('storefront::messages.home_saved'));
    }

    public function reset(BuilderEngine $engine, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $engine->reset();
        $audit->record('storefront.builder_reset', 'storefront', BuilderEngine::HOME);

        return back()->with('success', __('storefront::messages.home_reset'));
    }
}
