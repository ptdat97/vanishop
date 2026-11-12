<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Catalog\Http\Requests\Admin\ProductImagesRequest;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;

/**
 * Ảnh theo SKU trong màn hình sửa sản phẩm (ADR-045).
 */
final class ProductVariantImageController
{
    public function store(Style $product, Variant $variant, ProductImagesRequest $request, VariantImages $images): RedirectResponse
    {
        $this->ensureBelongs($product, $variant);
        $images->addFiles($product, $variant, array_values($request->file('images', [])));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function attach(Style $product, Variant $variant, Request $request, VariantImages $images): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $variant);
        $data = $request->validate(['media_ids' => ['required', 'array', 'min:1', 'max:'.VariantImages::MAX_IMAGES], 'media_ids.*' => ['integer']]);
        $images->attachMedia($product, $variant, array_map('intval', $data['media_ids']));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function reorder(Style $product, Variant $variant, Request $request, VariantImages $images): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $variant);
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
        $images->reorder($product, $variant, array_map('intval', $data['order']));

        return back()->with('success', __('catalog::messages.saved'));
    }

    /** Áp bộ ảnh của SKU này cho mọi SKU cùng giá trị ở một trục (vd. mọi size cùng màu). */
    public function copy(Style $product, Variant $variant, Request $request, VariantImages $images): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $variant);
        $data = $request->validate(['option_type_id' => ['required', 'integer']]);
        $count = $images->copyToSiblings($product, $variant, (int) $data['option_type_id']);

        return back()->with('success', __('catalog::messages.images_copied', ['count' => $count]));
    }

    public function destroy(Style $product, Variant $variant, Mediable $image, VariantImages $images): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->ensureBelongs($product, $variant);
        abort_unless($image->mediable_type === $variant->getMorphClass() && $image->mediable_id === $variant->id, 404);
        $images->remove($product, $variant, $image);

        return back()->with('success', __('catalog::messages.deleted'));
    }

    private function ensureBelongs(Style $product, Variant $variant): void
    {
        abort_unless($variant->style_id === $product->id, 404);
    }
}
