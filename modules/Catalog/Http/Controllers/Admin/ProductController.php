<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Application\Products\StyleLinkService;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Catalog\Application\Products\VariantMatrix;
use Modules\Catalog\Application\Products\VariantOptions;
use Modules\Catalog\Application\Products\VideoDrivers;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Http\Requests\Admin\ProductRequest;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleOption;
use Modules\Catalog\Persistence\Models\StyleOptionValue;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Persistence\Models\Video;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Facades\Hook;
use Modules\Shared\Domain\Text\VietnameseText;
use Modules\Shared\Http\ListSort;

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
        $sort = ListSort::fromRequest($request, ['style_code' => 'style_code', 'status' => 'status']);

        $styles = Style::query()
            ->with(['translations', 'brand:id,name', 'variants.gallery.media'])
            ->when($search !== '', function ($query) use ($search): void {
                foreach (VietnameseText::tokens($search) as $token) {
                    $query->where('search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->when(is_string($status) && StyleStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->when(($pluginIds = $screen->filterIds('product', (array) $request->query('ext', []))) !== null, fn ($query) => $query->whereIn('id', $pluginIds))
            ->when($sort, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('updated_at')->orderByDesc('id'))
            ->paginate($perPage)
            ->withQueryString();

        // Lượt xem 30 ngày (§3.5-2), một truy vấn cho cả trang.
        $viewCounts = $views->counts($styles->getCollection()->pluck('id')->all());

        return Inertia::render('Catalog::Products/Index', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'filters' => ['q' => $search, 'status' => $status],
            'sort' => $sort?->toArray(),
            'products' => [
                'data' => $styles->getCollection()->map(fn (Style $style): array => [
                    'views' => $viewCounts[$style->id] ?? 0,
                    'id' => $style->id,
                    'style_code' => $style->style_code,
                    'name' => $style->translate('name'),
                    'status' => $style->status->value,
                    'brand' => $style->brand?->name,
                    'skus' => $style->variants->count(),
                    'image_url' => $style->variants->flatMap(fn (Variant $variant) => $variant->gallery)->first()?->media->url(160),
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
     *     editValues: array<int, array<string, mixed>>,
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

    public function store(ProductRequest $request, ProductService $products, AdminScreen $screen, VariantMatrix $matrix, VariantImages $images): RedirectResponse
    {
        $request->validate($screen->validationRules('product'));
        $fieldErrors = [];
        // `function … use (&…)` cả hai tầng: arrow fn chụp biến theo giá trị nên lỗi cột không ra được ngoài.
        $style = $this->orFormError(function () use ($screen, $products, $request, $matrix, $images, &$fieldErrors): Style {
            return $screen->saving('product', (array) $request->input('extensions', []), function () use ($products, $request, $matrix, $images, &$fieldErrors): Style {
                $style = $products->create($request->toInput());
                $fieldErrors = $this->saveVariants($style, $request, $matrix);
                $this->attachInitialImages($style, $request, $images);

                return $style;
            }, fn ($style): int => $style->id);
        });

        return $this->withFieldErrors(redirect()->route('admin.catalog.products.edit', ['product' => $style->id])->with('success', __('catalog::messages.saved')), $fieldErrors);
    }

    public function edit(Style $product, CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form($product->load(['translations', 'categories:id', 'attributeValues', 'options.type', 'optionValues', 'variants.gallery.media', ...VariantOptions::relations('variants.'), 'videos']), $tree);
    }

    public function update(Style $product, ProductRequest $request, ProductService $products, AdminScreen $screen, VariantMatrix $matrix): RedirectResponse
    {
        $request->validate($screen->validationRules('product'));
        $fieldErrors = [];
        $this->orFormError(function () use ($screen, $product, $products, $request, $matrix, &$fieldErrors): void {
            $screen->saving('product', (array) $request->input('extensions', []), function () use ($product, $products, $request, $matrix, &$fieldErrors): Style {
                $style = $products->update($product, $request->toInput(), (int) $request->validated('lock_version'));
                $fieldErrors = $this->saveVariants($product, $request, $matrix);

                return $style;
            }, fn (): int => $product->id);
        });

        return $this->withFieldErrors(back()->with('success', __('catalog::messages.saved')), $fieldErrors);
    }

    public function destroy(Style $product, ProductService $products): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $products->deleteDraft($product);

        return redirect()->route('admin.catalog.products.index')->with('success', __('catalog::messages.deleted'));
    }

    /**
     * Ảnh chọn ngay khi thêm sản phẩm: gắn cho mọi SKU vừa tạo (cùng transaction — lỗi thì không tạo gì). Ảnh riêng theo màu
     * chỉnh sau ở ma trận biến thể. Có ảnh mà chưa có SKU → báo lỗi thay vì bỏ ảnh im lặng.
     */
    private function attachInitialImages(Style $style, ProductRequest $request, VariantImages $images): void
    {
        $mediaIds = array_map('intval', (array) $request->validated('media_ids', []));
        if ($mediaIds === []) {
            return;
        }
        $variantIds = $style->variants()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        if ($variantIds === []) {
            throw ValidationException::withMessages(['media_ids' => __('catalog::messages.images_need_variant')]);
        }
        $images->attachToVariants($style, $variantIds, $mediaIds);
    }

    /**
     * Ma trận biến thể gửi kèm form sản phẩm (một nút Lưu, variant-matrix §3.2): lưu trong cùng transaction với sản phẩm — lỗi
     * của ma trận (SKU trùng, giá trị không thuộc trục…) huỷ cả lần lưu. Không gửi `variants` → không đổi biến thể.
     *
     * @return array<int, string> lỗi của cột góp vào theo SKU (phần Catalog vẫn lưu)
     */
    private function saveVariants(Style $style, Request $request, VariantMatrix $matrix): array
    {
        $input = $request->input('variants');
        if (! is_array($input)) {
            return [];
        }

        return $matrix->save($style, $input, fn (string $permission): bool => Gate::allows($permission))['field_errors'];
    }

    /**
     * @param  array<int, string>  $fieldErrors
     */
    private function withFieldErrors(RedirectResponse $response, array $fieldErrors): RedirectResponse
    {
        return $fieldErrors === [] ? $response : $response->withErrors(array_combine(
            array_map(fn (int $variantId): string => "variant.{$variantId}", array_keys($fieldErrors)),
            array_values($fieldErrors),
        ));
    }

    private function form(?Style $style, CategoryTreeQuery $tree): Response
    {
        $attributes = Attribute::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get();
        $linkTypes = LinkType::query()->with('translations')->orderBy('position')->orderBy('code')->get();
        $videoDrivers = app(VideoDrivers::class);

        $optionTypes = OptionType::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get();
        $optionValueTypes = $optionTypes->flatMap(fn (OptionType $type) => $type->values)->mapWithKeys(fn (OptionValue $value): array => [$value->id => $value->option_type_id])->all();

        return Inertia::render('Catalog::Products/Form', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            // Ma trận biến thể (variant-matrix §3, ADR-045): trục của sản phẩm (1–3 loại tuỳ chọn + giá trị theo thứ tự), danh mục
            // loại tuỳ chọn để chọn trục, cột do module/plugin góp + giá trị theo SKU.
            'variantMatrix' => [
                ...app(VariantMatrix::class)->columns($style, fn (string $permission): bool => Gate::allows($permission)),
                // Sản phẩm mới: sẵn trục Màu + Size (chỉ cần chọn giá trị; bỏ trục nếu không cần).
                'axes' => $style === null
                    ? $optionTypes->whereIn('code', [OptionType::COLOR, OptionType::SIZE])->sortBy(fn (OptionType $type): int => $type->code === OptionType::COLOR ? 0 : 1)
                        ->map(fn (OptionType $type): array => ['option_type_id' => $type->id, 'value_ids' => []])->values()->all()
                    : $style->options->map(fn (StyleOption $axis): array => [
                        'option_type_id' => $axis->option_type_id,
                        'value_ids' => $style->optionValues->filter(fn (StyleOptionValue $row): bool => $optionValueTypes[$row->option_value_id] === $axis->option_type_id)->pluck('option_value_id')->values()->all(),
                    ])->values()->all(),
                'optionTypes' => $optionTypes->map(fn (OptionType $type): array => [
                    'id' => $type->id, 'code' => $type->code, 'name' => $type->translate('name') ?? $type->code, 'display_type' => $type->display_type->value,
                    'values' => $type->values->map(fn (OptionValue $value): array => ['id' => $value->id, 'code' => $value->code, 'name' => $value->label(), 'hex' => $value->hex, 'group' => $value->group])->all(),
                ])->values()->all(),
                // Tạo nhanh ngay trong form (không rời trang): loại tuỳ chọn, giá trị (`{type}` thay bằng id loại), thuộc tính.
                'urls' => [
                    'option_types' => route('admin.catalog.option-types.index'),
                    'option_type_store' => route('admin.catalog.option-types.store'),
                    'option_value_store' => str_replace('__TYPE__', '{type}', route('admin.catalog.option-types.values.store', ['optionType' => '__TYPE__'])),
                ],
            ],
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
                // SKU: giá trị theo thứ tự trục (id), ảnh của chính SKU (ADR-045).
                'variants' => $style->variants
                    ->sortBy(fn (Variant $variant): string => VariantOptions::sortKey($variant))
                    ->map(fn (Variant $variant): array => [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'barcode' => $variant->barcode,
                        'status' => $variant->status->value,
                        'weight_gram' => $variant->weight_gram,
                        'values' => array_map(fn (OptionValue $value): int => $value->id, VariantOptions::ordered($variant)),
                        'label' => implode(' / ', array_map(fn (OptionValue $value): string => $value->label(), VariantOptions::ordered($variant))),
                        'images' => $variant->gallery->map(fn ($image): array => ['id' => $image->id, 'url' => $image->media->url(400)])->all(),
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
            // Khối Thuộc tính luôn hiện (kể cả khi chưa có thuộc tính): tạo nhanh tại form, link tới trang quản lý.
            'attributeUrls' => ['index' => route('admin.catalog.attributes.index'), 'store' => route('admin.catalog.attributes.store')],
            'attributeDefinitions' => $attributes->map(fn (Attribute $attribute): array => [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'name' => $attribute->translate('name'),
                'kind' => $attribute->kind->value,
                'input_type' => $attribute->input_type->value,
                'values' => $attribute->values->map(fn ($value): array => ['id' => $value->id, 'label' => $value->translate('label')])->all(),
            ])->all(),
            'statuses' => array_column(StyleStatus::cases(), 'value'),
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
