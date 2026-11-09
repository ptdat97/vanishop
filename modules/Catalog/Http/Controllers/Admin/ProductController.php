<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Application\Products\StyleLinkService;
use Modules\Catalog\Application\Products\VideoDrivers;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Http\Requests\Admin\ProductRequest;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Persistence\Models\Video;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Facades\Hook;
use Modules\Shared\Domain\Text\VietnameseText;

final class ProductController
{
    use ConvertsDomainErrors;

    public function index(Request $request, AdminScreen $screen, ProductViewStatistics $views): Response
    {
        Gate::authorize('catalog.view');

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', Rule::in([30, 50, 100])],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 30);
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $styles = Style::query()
            ->with(['translations', 'brand:id,name', 'colors.gallery.media'])
            ->when($search !== '', function ($query) use ($search): void {
                foreach (VietnameseText::tokens($search) as $token) {
                    $query->where('search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->when(is_string($status) && StyleStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->when(($pluginIds = $screen->filterIds('product', (array) $request->query('ext', []))) !== null, fn ($query) => $query->whereIn('id', $pluginIds))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // Lượt xem 30 ngày (§3.5-2), một truy vấn cho cả trang.
        $viewCounts = $views->counts($styles->getCollection()->pluck('id')->all());

        return Inertia::render('Catalog::Products/Index', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'filters' => ['q' => $search, 'status' => $status],
            'products' => [
                'data' => $styles->getCollection()->map(fn (Style $style): array => [
                    'views' => $viewCounts[$style->id] ?? 0,
                    'id' => $style->id,
                    'style_code' => $style->style_code,
                    'name' => $style->translate('name'),
                    'status' => $style->status->value,
                    'brand' => $style->brand?->name,
                    'colors' => $style->colors->count(),
                    'image_url' => $style->colors->flatMap(fn (StyleColor $color) => $color->gallery)->first()?->media->url(160),
                ])->all(),
                'current' => $styles->currentPage(),
                'last' => $styles->lastPage(),
                'from' => $styles->firstItem(),
                'to' => $styles->lastItem(),
                'total' => $styles->total(),
                'per_page' => $styles->perPage(),
            ],
            'canManage' => Gate::allows('catalog.manage'),
            'canManagePrices' => Gate::allows('pricing.manage'),
            'extensions' => [
                ...$this->columns($screen, $styles->getCollection()->pluck('id')->all()),
                'filters' => $screen->filters('product'),
                'filterValues' => (array) $request->query('ext', []),
                'actions' => $this->bulkActions($screen),
            ],
        ]);
    }

    /**
     * Cột của plugin (AdminScreen) + của module Core (slot vani.admin.product.list_columns — giá, tồn).
     *
     * @param  list<int>  $ids
     * @return array{
     *     columns: list<array{key: string, label: string}>,
     *     values: array<int, array<string, scalar|null>>,
     *     editValues: array<int, array<string, int|null>>,
     *     urls: array<int, array<string, string>>
     * }
     */
    private function columns(AdminScreen $screen, array $ids): array
    {
        $result = $screen->columns('product', $ids);
        $editValues = [];
        $urls = [];
        foreach (array_filter(Hook::slot('vani.admin.product.list_columns', $ids)) as $column) {
            $result['columns'][] = ['key' => $column['key'], 'label' => $column['label']];
            foreach ($column['values'] as $styleId => $text) {
                $result['values'][$styleId][$column['key']] = $text;
            }
            foreach ($column['edit_values'] ?? [] as $styleId => $value) {
                $editValues[$styleId][$column['key']] = $value;
            }
            foreach ($column['urls'] ?? [] as $styleId => $url) {
                $urls[$styleId][$column['key']] = $url;
            }
        }

        return [
            'columns' => $result['columns'],
            'values' => $result['values'],
            'editValues' => $editValues,
            'urls' => $urls,
        ];
    }

    /**
     * Thao tác hàng loạt (0.3.103): Mở bán / Ngừng bán (Catalog) + module Core (đổi giá…) + plugin.
     *
     * @return list<array<string, mixed>>
     */
    private function bulkActions(AdminScreen $screen): array
    {
        $actions = Gate::allows('catalog.manage') ? [
            ['key' => 'activate', 'label' => 'Mở bán', 'url' => route('admin.catalog.products.bulk-status'), 'confirm' => true, 'data' => ['status' => 'active']],
            ['key' => 'archive', 'label' => 'Ngừng bán (ẩn khỏi cửa hàng)', 'url' => route('admin.catalog.products.bulk-status'), 'confirm' => true, 'data' => ['status' => 'archived']],
        ] : [];
        foreach (array_filter(Hook::slot('vani.admin.product.bulk_actions'), 'is_array') as $contributed) {
            array_push($actions, ...$contributed);
        }
        array_push($actions, ...$screen->actions('product', 'bulk'));

        return $actions;
    }

    /**
     * Mở bán / ngừng bán / chuyển nháp nhiều sản phẩm (0.3.103).
     */
    public function bulkStatus(Request $request, ProductService $products): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer', 'min:1'],
            'status' => ['required', Rule::enum(StyleStatus::class)],
        ]);
        $status = StyleStatus::from($data['status']);
        $changed = 0;
        foreach (array_unique(array_map('intval', $data['ids'])) as $id) {
            $changed += Style::query()->whereKey($id)->exists() && $products->changeStatus($id, $status) ? 1 : 0;
        }

        return back()->with('success', __('catalog::messages.bulk_status_changed', ['count' => $changed, 'status' => __("catalog::labels.product_status.{$status->value}")]));
    }

    public function create(CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form(null, $tree);
    }

    public function store(ProductRequest $request, ProductService $products, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('product'));
        $style = $this->orFormError(fn () => $screen->saving('product', (array) $request->input('extensions', []), fn () => $products->create($request->toInput()), fn ($style): int => $style->id));

        return redirect()->route('admin.catalog.products.edit', ['product' => $style->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Style $product, CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form($product->load(['translations', 'categories:id', 'attributeValues', 'colors.color.translations', 'colors.gallery.media', 'variants.size', 'variants.styleColor.color', 'videos']), $tree);
    }

    public function update(Style $product, ProductRequest $request, ProductService $products, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('product'));
        $this->orFormError(fn () => $screen->saving('product', (array) $request->input('extensions', []), fn () => $products->update($product, $request->toInput(), (int) $request->validated('lock_version')), fn (): int => $product->id));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Style $product, ProductService $products): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $products->deleteDraft($product);

        return redirect()->route('admin.catalog.products.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(?Style $style, CategoryTreeQuery $tree): Response
    {
        $attributes = Attribute::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get();
        $linkTypes = LinkType::query()->with('translations')->orderBy('position')->orderBy('code')->get();
        $videoDrivers = app(VideoDrivers::class);

        return Inertia::render('Catalog::Products/Form', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
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
                'brand_id' => $style->brand_id,
                'translations' => $style->translationsByLocale(),
                'attributes' => $this->attributeFormValues($style, $attributes),
                'colors' => $style->colors->map(fn (StyleColor $styleColor): array => [
                    'id' => $styleColor->id,
                    'code' => $styleColor->color->code,
                    'name' => $styleColor->color->translate('name'),
                    'hex' => $styleColor->color->hex,
                    'images' => $styleColor->gallery->map(fn ($image): array => ['id' => $image->id, 'url' => $image->media->url(400)])->all(),
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
                'links' => $this->linkFormValues($style, $linkTypes),
                // Driver đã tắt/gỡ: vẫn liệt kê để gỡ được, storefront không hiện.
                'videos' => $style->videos->map(fn (Video $video): array => [
                    'id' => $video->id,
                    'driver' => $video->driver,
                    'reference' => $video->reference,
                    'title' => $video->title,
                    'available' => ($view = $videoDrivers->view($video)) !== null,
                    'watch_url' => $view['watch_url'] ?? null,
                    'thumbnail_url' => $view['thumbnail_url'] ?? null,
                ])->all(),
            ],
            'linkTypes' => $linkTypes->map(fn (LinkType $type): array => ['id' => $type->id, 'label' => $type->translate('name') ?? $type->code, 'is_active' => $type->is_active])->all(),
            'videoDrivers' => array_values(array_map(fn ($driver): array => ['code' => $driver->code(), 'label' => $driver->label(), 'hint' => $driver->inputHint()], $videoDrivers->all())),
            'extensionSections' => app(AdminScreen::class)->formSections('product', $style?->id),
            // Lưới tồn kho lọc theo sản phẩm (module Inventory — link theo tên route, Catalog không phụ thuộc Inventory).
            'stockUrl' => $style !== null && Route::has('admin.inventory.stock.index') && Gate::allows('inventory.view') ? route('admin.inventory.stock.index', ['style' => $style->style_code]) : null,
            'categories' => $this->categoryOptions($tree->tree()),
            'brands' => Brand::query()->orderBy('position')->orderBy('name')->get(['id', 'name'])->map(fn (Brand $brand): array => ['id' => $brand->id, 'label' => $brand->name])->all(),
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
     * Sản phẩm liên kết mà sản phẩm này nhìn thấy, theo loại.
     *
     * @param  Collection<int, LinkType>  $linkTypes
     * @return list<array{type_id: int, name: string, products: list<array{id: int, style_code: string, name: string|null}>}>
     */
    private function linkFormValues(Style $style, Collection $linkTypes): array
    {
        $linked = app(StyleLinkService::class)->linkedStyleIds($style->id);
        $styles = Style::query()->with('translations')->whereKey(array_merge([], ...array_values($linked)))->get()->keyBy('id');

        return $linkTypes->filter(fn (LinkType $type): bool => isset($linked[$type->id]))
            ->map(fn (LinkType $type): array => [
                'type_id' => $type->id,
                'name' => $type->translate('name') ?? $type->code,
                'products' => array_values(array_filter(array_map(fn (int $id): ?array => $styles->has($id)
                    ? ['id' => $id, 'style_code' => $styles[$id]->style_code, 'name' => $styles[$id]->translate('name')] : null, $linked[$type->id]))),
            ])->values()->all();
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
