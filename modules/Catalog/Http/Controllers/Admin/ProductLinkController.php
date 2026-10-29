<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Application\Products\StyleLinkService;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Style;

/**
 * Sản phẩm liên kết (tương tự, mua kèm…) của một sản phẩm, nhập theo mã sản phẩm.
 */
final class ProductLinkController
{
    public function store(Style $product, Request $request, StyleLinkService $links): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate([
            'link_type_id' => ['required', 'integer', 'exists:link_types,id'],
            'style_codes' => ['required', 'array', 'min:1', 'max:'.StyleLinkService::MAX_LINKS_PER_TYPE],
            'style_codes.*' => ['string', 'max:64'],
            'one_way' => ['boolean'],
        ]);

        $added = $links->link($product, LinkType::query()->findOrFail($data['link_type_id']), array_values($data['style_codes']), (bool) ($data['one_way'] ?? false));

        return back()->with('success', __('catalog::messages.links_added', ['count' => $added]));
    }

    public function destroy(Style $product, LinkType $linkType, Style $linked, StyleLinkService $links): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $links->unlink($product, $linkType, $linked);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
