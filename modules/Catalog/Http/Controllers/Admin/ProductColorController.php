<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Application\Products\StyleColorService;
use Modules\Catalog\Http\Requests\Admin\ProductImagesRequest;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleColor;

/**
 * Màu và bộ ảnh theo màu của sản phẩm. Luôn kiểm tra StyleColor/Mediable thuộc đúng sản phẩm trong URL.
 */
final class ProductColorController
{
    public function store(Style $product, Request $request, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate(['color_id' => ['required', 'integer']]);

        $colors->addColor($product, (int) $data['color_id']);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Style $product, StyleColor $styleColor, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $styleColor);

        $colors->removeColor($product, $styleColor);

        return back()->with('success', __('catalog::messages.deleted'));
    }

    public function storeImages(Style $product, StyleColor $styleColor, ProductImagesRequest $request, StyleColorService $colors): RedirectResponse
    {
        $this->ensureBelongs($product, $styleColor);

        $colors->addImages($product, $styleColor, array_values($request->file('images', [])));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function attachLibraryImages(Style $product, StyleColor $styleColor, Request $request, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $styleColor);
        $data = $request->validate(['media_ids' => ['required', 'array', 'min:1', 'max:'.StyleColorService::MAX_IMAGES_PER_COLOR], 'media_ids.*' => ['integer']]);

        $colors->attachMedia($product, $styleColor, array_map('intval', $data['media_ids']));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroyImage(Style $product, StyleColor $styleColor, Mediable $image, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $styleColor);
        abort_unless($image->mediable_type === 'catalog.style_color' && $image->mediable_id === $styleColor->id, 404);

        $colors->removeImage($product, $styleColor, $image);

        return back()->with('success', __('catalog::messages.deleted'));
    }

    public function reorderImages(Style $product, StyleColor $styleColor, Request $request, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $styleColor);
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        $colors->reorderImages($product, $styleColor, array_map('intval', $data['order']));

        return back()->with('success', __('catalog::messages.saved'));
    }

    private function ensureBelongs(Style $product, StyleColor $styleColor): void
    {
        abort_unless($styleColor->style_id === $product->id, 404);
    }
}
