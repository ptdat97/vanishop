<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Application\AdminQueries;
use Modules\Catalog\Application\Products\ProductLinkService;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Product;

/**
 * Sản phẩm liên kết (tương tự, mua kèm…) của một sản phẩm, nhập theo mã sản phẩm.
 */
final class ProductLinkController
{
    public function __construct(private readonly AdminQueries $queries) {}

    public function store(Product $product, Request $request, ProductLinkService $links): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate([
            'link_type_id' => ['required', 'integer', 'exists:link_types,id'],
            'product_codes' => ['required', 'array', 'min:1', 'max:'.ProductLinkService::MAX_LINKS_PER_TYPE],
            'product_codes.*' => ['string', 'max:64'],
            'one_way' => ['boolean'],
        ]);

        $added = $links->link($product, $this->queries->linkType((int) $data['link_type_id']), array_values($data['product_codes']), (bool) ($data['one_way'] ?? false));

        return back()->with('success', __('catalog::messages.links_added', ['count' => $added]));
    }

    public function destroy(Product $product, LinkType $linkType, Product $linked, ProductLinkService $links): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $links->unlink($product, $linkType, $linked);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
