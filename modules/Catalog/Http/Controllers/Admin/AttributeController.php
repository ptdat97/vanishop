<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Http\Requests\Admin\AttributeRequest;
use Modules\Catalog\Persistence\Models\Attribute;

final class AttributeController
{
    use ConvertsDomainErrors;

    public function index(): Response
    {
        Gate::authorize('catalog.view');

        $attributes = Attribute::query()->with(['translations'])->withCount('values')->orderBy('position')->orderBy('code')->get();

        return Inertia::render('Catalog::Taxonomy/Attributes', [
            'nav' => CatalogNavigation::all(),
            'attributes' => $attributes->map(fn (Attribute $attribute): array => [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'name' => $attribute->translate('name'),
                'kind' => $attribute->kind->value,
                'input_type' => $attribute->input_type->value,
                'is_filterable' => $attribute->is_filterable,
                'values_count' => $attribute->values_count,
            ])->all(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form(null);
    }

    public function store(AttributeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $attribute = $taxonomy->saveAttribute($request->toData());

        return redirect()->route('admin.catalog.attributes.edit', ['attribute' => $attribute->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Attribute $attribute): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form($attribute->load(['translations', 'values.translations']));
    }

    public function update(Attribute $attribute, AttributeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $this->orFormError(fn () => $taxonomy->saveAttribute($request->toData(), $attribute, (int) $request->validated('lock_version')));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Attribute $attribute, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $taxonomy->deleteAttribute($attribute);

        return redirect()->route('admin.catalog.attributes.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(?Attribute $attribute): Response
    {
        return Inertia::render('Catalog::Taxonomy/AttributeForm', [
            'nav' => CatalogNavigation::all(),
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
