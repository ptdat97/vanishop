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
use Modules\Storefront\Contracts\BuilderSurface;

/**
 * Admin → Giao diện → Nội dung trang: biên tập block của mọi surface (Core + plugin) bằng một màn hình chung,
 * cấu hình theo fields() của từng loại block và giới hạn của từng vùng.
 */
final class BuilderController
{
    public function home(): RedirectResponse
    {
        return redirect()->route('admin.storefront.builder.edit', BuilderEngine::HOME);
    }

    public function edit(string $surface, BuilderEngine $engine, PageBlocks $blocks, Maintenance $maintenance): Response
    {
        Gate::authorize('storefront.manage');
        $definition = $this->surface($engine, $surface);
        $stored = $engine->blocks($surface);
        $layout = $engine->layout($definition);

        return Inertia::render('Storefront::Design/Builder', [
            'surfaces' => array_values(array_map(fn (BuilderSurface $candidate): array => [
                'key' => $candidate->key(),
                'label' => $candidate->label(),
                'url' => route('admin.storefront.builder.edit', $candidate->key()),
            ], $engine->surfaces())),
            'surface' => ['key' => $surface, 'label' => $definition->label()],
            'types' => $blocks->editorTypes(),
            'allowedZones' => $blocks->editorZones($layout),
            'zones' => $layout->toArray(),
            'blocks' => $blocks->editorBlocks($stored ?? []),
            'draftUrls' => [
                'preview' => route('admin.storefront.builder.draft', [$surface, 'preview']),
                'import' => route('admin.storefront.builder.draft', [$surface, 'import']),
            ],
            'configured' => $stored !== null,
            // Đang bảo trì: đi qua route phát cookie xem trước, không thì nhân viên chỉ thấy trang 503.
            'previewUrl' => $surface !== BuilderEngine::HOME ? null
                : ($maintenance->enabled() ? route('admin.storefront.maintenance.preview') : route('storefront.home')),
        ]);
    }

    public function update(Request $request, string $surface, BuilderEngine $engine, PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $definition = $this->surface($engine, $surface);
        $data = $request->validate($blocks->inputRules($engine->layout($definition)));
        $clean = $engine->save($surface, $data['blocks']);
        $audit->record('storefront.builder_updated', 'storefront', $surface, ['count' => count($clean)]);

        return back()->with('success', __('storefront::messages.builder_saved', ['surface' => $definition->label()]));
    }

    public function reset(string $surface, BuilderEngine $engine, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $this->surface($engine, $surface);
        $engine->reset($surface);
        $audit->record('storefront.builder_reset', 'storefront', $surface);

        return back()->with('success', __($surface === BuilderEngine::HOME ? 'storefront::messages.home_reset' : 'storefront::messages.builder_reset'));
    }

    private function surface(BuilderEngine $engine, string $surface): BuilderSurface
    {
        $definition = $engine->surface($surface);
        abort_if($definition === null, 404);

        return $definition;
    }
}
