<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Products\VariantService;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Identity\Contracts\Data\ScopeRef;

final class ProductVariantController
{
    public function generate(Brand $brand, Style $product, Request $request, VariantService $variants): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $data = $request->validate(['size_ids' => ['required', 'array', 'min:1', 'max:50'], 'size_ids.*' => ['integer', 'distinct']]);

        $created = $variants->generate($product, array_map('intval', $data['size_ids']));

        return back()->with('success', __('catalog::messages.variants_generated', ['count' => count($created)]));
    }

    public function update(Brand $brand, Style $product, Variant $variant, Request $request, VariantService $variants): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        abort_unless($variant->style_id === $product->id, 404);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', Rule::unique('variants', 'sku')->ignore($variant->id)],
            'barcode' => ['nullable', 'string', 'max:32', 'regex:/^[0-9A-Za-z-]+$/', Rule::unique('variants', 'barcode')->ignore($variant->id)],
            'status' => ['required', Rule::enum(VariantStatus::class)],
            'weight_gram' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $variants->update($product, $variant, [
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'status' => $data['status'],
            'weight_gram' => $data['weight_gram'] ?? null,
        ]);

        return back()->with('success', __('catalog::messages.saved'));
    }
}
