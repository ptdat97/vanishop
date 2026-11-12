<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Application\Products\VariantMatrix;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;

/**
 * Ma trận biến thể màu × size (variant-matrix §3): lưu cả lưới một lần — SKU của Catalog và cột do module/plugin góp vào.
 */
final class ProductVariantController
{
    public function save(Style $product, Request $request, VariantMatrix $matrix): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $result = $matrix->save($product, $request->all(), fn (string $permission): bool => Gate::allows($permission));

        $response = back()->with('success', __('catalog::messages.variants_matrix_saved', array_diff_key($result, ['field_errors' => true])));

        return $result['field_errors'] === [] ? $response : $response->withErrors(array_combine(
            array_map(fn (int $variantId): string => "variant.{$variantId}", array_keys($result['field_errors'])),
            array_values($result['field_errors']),
        ));
    }
}
