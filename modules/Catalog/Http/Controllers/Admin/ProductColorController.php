<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Products\StyleColorService;
use Modules\Catalog\Http\Requests\Admin\ProductImagesRequest;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Identity\Contracts\Data\ScopeRef;

/**
 * Màu và bộ ảnh theo màu của sản phẩm. StyleColor/Mediable không tự mang brand_id nên luôn kiểm tra
 * chúng thuộc đúng sản phẩm (sản phẩm đã được lọc theo brand workspace).
 */
final class ProductColorController
{
    public function store(Brand $brand, Style $product, Request $request, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $data = $request->validate(['color_id' => ['required', 'integer']]);

        $colors->addColor($product, (int) $data['color_id']);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, Style $product, StyleColor $styleColor, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $this->ensureBelongs($product, $styleColor);

        $colors->removeColor($product, $styleColor);

        return back()->with('success', __('catalog::messages.deleted'));
    }

    public function storeImages(Brand $brand, Style $product, StyleColor $styleColor, ProductImagesRequest $request, StyleColorService $colors): RedirectResponse
    {
        $this->ensureBelongs($product, $styleColor);

        $colors->addImages($product, $styleColor, array_values($request->file('images', [])));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroyImage(Brand $brand, Style $product, StyleColor $styleColor, Mediable $image, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $this->ensureBelongs($product, $styleColor);
        abort_unless($image->mediable_type === 'catalog.style_color' && $image->mediable_id === $styleColor->id, 404);

        $colors->removeImage($product, $styleColor, $image);

        return back()->with('success', __('catalog::messages.deleted'));
    }

    public function reorderImages(Brand $brand, Style $product, StyleColor $styleColor, Request $request, StyleColorService $colors): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
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
