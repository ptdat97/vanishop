<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Admin → Giao diện → Trang chủ: sắp xếp khối (page builder), cấu hình theo fields() của từng loại khối.
 */
final class HomeBlocksController
{
    public function home(): RedirectResponse
    {
        return redirect()->route('admin.storefront.home-blocks.edit');
    }

    public function edit(PageBlocks $blocks): Response
    {
        Gate::authorize('storefront.manage');

        return Inertia::render('Storefront::Design/HomeBlocks', [
            'types' => array_values(array_map(fn (StorefrontBlock $type): array => [
                'type' => $type->type(),
                'label' => $type->label(),
                'fields' => array_map(fn (FieldDefinition $field): array => $field->toArray(), $type->fields()),
            ], $blocks->types())),
            'blocks' => $blocks->home() ?? [],
            'configured' => $blocks->home() !== null,
            'previewUrl' => route('storefront.home'),
        ]);
    }

    public function update(Request $request, PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $data = $request->validate(['blocks' => ['present', 'array'], 'blocks.*.type' => ['required', 'string', 'max:64'], 'blocks.*.config' => ['nullable', 'array']]);
        $blocks->saveHome($data['blocks']);
        $audit->record('storefront.home_blocks_updated', 'storefront', 'home', ['count' => count($data['blocks'])]);

        return back()->with('success', __('storefront::messages.home_saved'));
    }

    public function reset(PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $blocks->resetHome();
        $audit->record('storefront.home_blocks_reset', 'storefront', 'home');

        return back()->with('success', __('storefront::messages.home_reset'));
    }
}
