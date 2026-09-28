<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Http\Requests\Admin\ProductRequest;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Shared\Domain\Text\VietnameseText;

final class ProductController
{
    use ConvertsDomainErrors;

    public function index(Brand $brand, Request $request): Response
    {
        Gate::authorize('catalog.view', [ScopeRef::brand($brand->id)]);

        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $styles = Style::query()
            ->with(['translations', 'colors.gallery.media'])
            ->when($search !== '', function ($query) use ($search): void {
                foreach (VietnameseText::tokens($search) as $token) {
                    $query->where('search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->when(is_string($status) && StyleStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Catalog::Products/Index', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'filters' => ['q' => $search, 'status' => $status],
            'products' => [
                'data' => $styles->getCollection()->map(fn (Style $style): array => [
                    'id' => $style->id,
                    'style_code' => $style->style_code,
                    'name' => $style->translate('name'),
                    'status' => $style->status->value,
                    'colors' => $style->colors->count(),
                    'image_url' => $style->colors->flatMap(fn (StyleColor $color) => $color->gallery)->first()?->media->url(),
                ])->all(),
                'links' => ['prev' => $styles->previousPageUrl(), 'next' => $styles->nextPageUrl()],
                'total' => $styles->total(),
            ],
            'canManage' => Gate::allows('catalog.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function create(Brand $brand, CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, null, $tree);
    }

    public function store(Brand $brand, ProductRequest $request, ProductService $products): RedirectResponse
    {
        $style = $this->orFormError(fn () => $products->create($brand->id, $request->toInput()));

        return redirect()->route('admin.catalog.products.edit', ['product' => $style->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Brand $brand, Style $product, CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, $product->load(['translations', 'categories:id', 'attributeValues', 'colors.color.translations', 'colors.gallery.media', 'variants.size', 'variants.styleColor.color']), $tree);
    }

    public function update(Brand $brand, Style $product, ProductRequest $request, ProductService $products): RedirectResponse
    {
        $this->orFormError(fn () => $products->update($product, $request->toInput(), (int) $request->validated('lock_version')));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, Style $product, ProductService $products): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        $products->deleteDraft($product);

        return redirect()->route('admin.catalog.products.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(Brand $brand, ?Style $style, CategoryTreeQuery $tree): Response
    {
        $attributes = Attribute::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get();

        return Inertia::render('Catalog::Products/Form', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'product' => $style === null ? null : [
                'id' => $style->id,
                'style_code' => $style->style_code,
                'slug' => $style->slug,
                'status' => $style->status->value,
                'published_from' => $style->published_from?->format('Y-m-d\TH:i'),
                'published_to' => $style->published_to?->format('Y-m-d\TH:i'),
                'lock_version' => $style->lock_version,
                'category_ids' => $style->categories->pluck('id')->all(),
                'primary_category_id' => $style->primary_category_id,
                'translations' => $style->translationsByLocale(),
                'attributes' => $this->attributeFormValues($style, $attributes),
                'colors' => $style->colors->map(fn (StyleColor $styleColor): array => [
                    'id' => $styleColor->id,
                    'code' => $styleColor->color->code,
                    'name' => $styleColor->color->translate('name'),
                    'hex' => $styleColor->color->hex,
                    'images' => $styleColor->gallery->map(fn ($image): array => ['id' => $image->id, 'url' => $image->media->url()])->all(),
                ])->all(),
                'variants' => $style->variants
                    ->sortBy(fn (Variant $variant): string => sprintf('%05d-%05d', $variant->styleColor->position, $variant->size->sort_order))
                    ->map(fn (Variant $variant): array => [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'barcode' => $variant->barcode,
                        'status' => $variant->status->value,
                        'weight_gram' => $variant->weight_gram,
                        'color_code' => $variant->styleColor->color->code,
                        'size_code' => $variant->size->code,
                    ])->values()->all(),
            ],
            'categories' => $this->categoryOptions($tree->tree()),
            'attributeDefinitions' => $attributes->map(fn (Attribute $attribute): array => [
                'id' => $attribute->id,
                'name' => $attribute->translate('name'),
                'kind' => $attribute->kind->value,
                'input_type' => $attribute->input_type->value,
                'values' => $attribute->values->map(fn ($value): array => ['id' => $value->id, 'label' => $value->translate('label')])->all(),
            ])->all(),
            'availableColors' => Color::query()->with('translations')->orderBy('position')->get()
                ->map(fn (Color $color): array => ['id' => $color->id, 'label' => $color->code.' — '.$color->translate('name')])->all(),
            'statuses' => array_column(StyleStatus::cases(), 'value'),
            'availableSizes' => Size::query()->orderBy('size_system')->orderBy('sort_order')->get()
                ->map(fn (Size $size): array => ['id' => $size->id, 'label' => $size->code, 'system' => $size->size_system->value])->all(),
        ]);
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     * @return array<int, mixed>
     */
    private function attributeFormValues(Style $style, $attributes): array
    {
        $rows = $style->attributeValues->groupBy('attribute_id');
        $values = [];
        foreach ($attributes as $attribute) {
            $group = $rows->get($attribute->id);
            $values[$attribute->id] = match ($attribute->input_type->value) {
                'multiselect' => $group?->pluck('attribute_value_id')->all() ?? [],
                'select' => $group?->first()?->attribute_value_id,
                'boolean' => $group?->first()?->value_bool,
                default => $group?->first()?->value_text,
            };
        }

        return $values;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array{id: int, label: string}>
     */
    private function categoryOptions(array $nodes): array
    {
        $options = [];
        foreach ($nodes as $node) {
            $options[] = ['id' => $node['id'], 'label' => str_repeat('— ', $node['depth'] - 1).$node['name']];
            $options = [...$options, ...$this->categoryOptions($node['children'])];
        }

        return $options;
    }
}
