<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Search\FacetSettings;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;

/**
 * Admin → Catalog → Bộ lọc (reference-parity §3.5-1): bật/tắt, thứ tự, kiểu hiển thị bộ lọc của trang danh sách.
 */
final class FacetController
{
    public function index(FacetSettings $facets): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Taxonomy/Filters', [
            'nav' => CatalogNavigation::all(),
            'facets' => $facets->configured(StoreLocale::default()),
            'displayLabels' => __('catalog::labels.facet_display'),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function update(Request $request, FacetSettings $facets, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate(['facets' => ['present', 'array', 'max:100'], 'facets.*.key' => ['required', 'string', 'max:80'], 'facets.*.enabled' => ['boolean'], 'facets.*.display' => ['required', 'string', 'max:16']]);
        $facets->save($data['facets'], StoreLocale::default());
        $audit->record('catalog.facets_updated', 'catalog', 'facets', ['enabled' => array_values(array_map(fn (array $facet): string => $facet['key'], array_filter($data['facets'], fn (array $facet): bool => (bool) ($facet['enabled'] ?? true))))]);

        return back()->with('success', __('catalog::messages.facets_saved'));
    }
}
