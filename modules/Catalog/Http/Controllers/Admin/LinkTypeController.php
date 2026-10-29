<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Http\Requests\Admin\LinkTypeRequest;
use Modules\Catalog\Persistence\Models\LinkType;

final class LinkTypeController
{
    public function index(): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Taxonomy/LinkTypes', [
            'nav' => CatalogNavigation::all(),
            'linkTypes' => LinkType::query()->with('translations')->withCount('groups')->orderBy('position')->orderBy('code')->get()
                ->map(fn (LinkType $type): array => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'is_active' => $type->is_active,
                    'position' => $type->position,
                    'groups_count' => $type->groups_count,
                    'translations' => $type->translationsByLocale(),
                ])->all(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function store(LinkTypeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->saveLinkType($request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(LinkType $linkType, LinkTypeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->saveLinkType($request->toData(), $linkType);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(LinkType $linkType, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->deleteLinkType($linkType);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
