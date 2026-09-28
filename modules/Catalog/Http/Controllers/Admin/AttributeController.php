<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Http\Requests\Admin\AttributeRequest;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Identity\Contracts\Data\ScopeRef;

final class AttributeController
{
    use ConvertsDomainErrors;

    public function index(Brand $brand): Response
    {
        Gate::authorize('catalog.view', [ScopeRef::brand($brand->id)]);

        $attributes = Attribute::query()->with(['translations'])->withCount('values')->orderBy('position')->orderBy('code')->get();

        return Inertia::render('Catalog::Taxonomy/Attributes', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'attributes' => $attributes->map(fn (Attribute $attribute): array => [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'name' => $attribute->translate('name'),
                'kind' => $attribute->kind->value,
                'input_type' => $attribute->input_type->value,
                'is_filterable' => $attribute->is_filterable,
                'values_count' => $attribute->values_count,
            ])->all(),
            'canManage' => Gate::allows('catalog.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function create(Brand $brand): Response
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, null);
    }

    public function store(Brand $brand, AttributeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        $attribute = $taxonomy->saveAttribute($brand->id, $request->toData());

        return redirect()->route('admin.catalog.attributes.edit', ['attribute' => $attribute->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Brand $brand, Attribute $attribute): Response
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, $attribute->load(['translations', 'values.translations']));
    }

    public function update(Brand $brand, Attribute $attribute, AttributeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        $this->orFormError(fn () => $taxonomy->saveAttribute($brand->id, $request->toData(), $attribute, (int) $request->validated('lock_version')));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, Attribute $attribute, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        $taxonomy->deleteAttribute($attribute);

        return redirect()->route('admin.catalog.attributes.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(Brand $brand, ?Attribute $attribute): Response
    {
        return Inertia::render('Catalog::Taxonomy/AttributeForm', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'attribute' => $attribute === null ? null : [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'kind' => $attribute->kind->value,
                'input_type' => $attribute->input_type->value,
                'is_filterable' => $attribute->is_filterable,
                'position' => $attribute->position,
                'lock_version' => $attribute->lock_version,
                'translations' => $attribute->translationsByLocale(),
                'values' => $attribute->values->map(fn ($value): array => [
                    'code' => $value->code,
                    'translations' => $value->translationsByLocale(),
                ])->all(),
            ],
            'kinds' => array_column(AttributeKind::cases(), 'value'),
            'inputTypes' => array_column(AttributeInputType::cases(), 'value'),
        ]);
    }
}
