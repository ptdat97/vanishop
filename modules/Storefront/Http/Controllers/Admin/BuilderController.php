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
use Modules\Storefront\Contracts\Data\BuilderScope;
use Modules\Storefront\Contracts\ScopedBuilderSurface;

/**
 * Admin → Giao diện → Nội dung trang: biên tập block của mọi surface (Core + plugin) bằng một màn hình chung,
 * cấu hình theo fields() của từng loại block và giới hạn của từng vùng. Surface theo phạm vi
 * ({@see ScopedBuilderSurface}) không có tab; plugin mở `builder/{surface}/{scope}` cho từng đối tượng.
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
                'plugin' => $engine->ownerOf($candidate),
                'url' => route('admin.storefront.builder.edit', $candidate->key()),
            ], array_filter($engine->surfaces(), fn (BuilderSurface $candidate): bool => ! $candidate instanceof ScopedBuilderSurface))),
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

    public function editScope(string $surface, string $scope, BuilderEngine $engine, PageBlocks $blocks): Response
    {
        Gate::authorize('storefront.manage');
        [$definition, $described] = $this->scoped($engine, $surface, $scope);
        $stored = $engine->blocks($surface, $scope);
        $layout = $engine->layout($definition);

        return Inertia::render('Storefront::Design/Builder', [
            'surface' => ['key' => $surface, 'label' => $definition->label()],
            'scope' => ['key' => $scope, 'label' => $described->label, 'backUrl' => $described->backUrl],
            'types' => $blocks->editorTypes(),
            'allowedZones' => $blocks->editorZones($layout),
            'zones' => $layout->toArray(),
            'blocks' => $blocks->editorBlocks($stored ?? []),
            'draftUrls' => [
                'preview' => route('admin.storefront.builder.scope.draft', [$surface, $scope, 'preview']),
                'import' => route('admin.storefront.builder.scope.draft', [$surface, $scope, 'import']),
            ],
            'configured' => $stored !== null,
            'previewUrl' => $described->previewUrl,
        ]);
    }

    public function updateScope(Request $request, string $surface, string $scope, BuilderEngine $engine, PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        [$definition, $described] = $this->scoped($engine, $surface, $scope);
        $data = $request->validate($blocks->inputRules($engine->layout($definition)));
        $clean = $engine->save($surface, $data['blocks'], $scope);
        $audit->record('storefront.builder_updated', 'storefront', "{$surface}:{$scope}", ['count' => count($clean)]);

        return back()->with('success', __('storefront::messages.builder_saved', ['surface' => $described->label]));
    }

    public function resetScope(string $surface, string $scope, BuilderEngine $engine, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $this->scoped($engine, $surface, $scope);
        $engine->reset($surface, $scope);
        $audit->record('storefront.builder_reset', 'storefront', "{$surface}:{$scope}");

        return back()->with('success', __('storefront::messages.builder_reset'));
    }

    /** Surface thường; surface theo phạm vi chỉ sửa qua `builder/{surface}/{scope}`. */
    private function surface(BuilderEngine $engine, string $surface): BuilderSurface
    {
        $definition = $engine->surface($surface);
        abort_if($definition === null || $definition instanceof ScopedBuilderSurface, 404);

        return $definition;
    }

    /** @return array{ScopedBuilderSurface, BuilderScope} */
    private function scoped(BuilderEngine $engine, string $surface, string $scope): array
    {
        $definition = $engine->surface($surface);
        abort_if(! $definition instanceof ScopedBuilderSurface || ! BuilderEngine::isValidScope($scope), 404);
        $described = $definition->describeScope($scope);
        abort_if($described === null, 404);

        return [$definition, $described];
    }
}
