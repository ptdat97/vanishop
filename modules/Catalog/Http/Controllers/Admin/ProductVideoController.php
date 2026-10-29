<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Application\Products\StyleVideoService;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Video;

/**
 * Video của sản phẩm. Luôn kiểm tra video thuộc đúng sản phẩm trong URL.
 */
final class ProductVideoController
{
    public function store(Style $product, Request $request, StyleVideoService $videos): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate([
            'driver' => ['required', 'string', 'max:32'],
            'url' => ['required', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $videos->add($product, $data['driver'], $data['url'], $data['title'] ?? null);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function reorder(Style $product, Request $request, StyleVideoService $videos): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        $videos->reorder($product, array_map('intval', $data['order']));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Style $product, Video $video, StyleVideoService $videos): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        abort_unless($product->videos()->whereKey($video->id)->exists(), 404);

        $videos->remove($product, $video);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
