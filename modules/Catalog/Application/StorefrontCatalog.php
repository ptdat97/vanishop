<?php

declare(strict_types=1);

namespace Modules\Catalog\Application;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Application\Products\StorefrontProductQuery;
use Modules\Catalog\Application\Search\FacetSettings;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\MediaUrls;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\AttributeValue;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Shared\Support\StoreLocale;

/**
 * Hiện thực CatalogReader cho storefront (native và Storefront API).
 */
final class StorefrontCatalog implements CatalogReader
{
    public function __construct(
        private readonly CategoryTreeQuery $categories,
        private readonly StorefrontProductQuery $products,
        private readonly FacetSettings $facetSettings,
    ) {}

    public function categoryTree(string $locale): array
    {
        return vani_filter('vani.service.catalog.category_tree', $this->present($this->categories->tree(activeOnly: true, locale: $locale)), $locale);
    }

    public function category(string $slug, string $locale): ?array
    {
        return $this->find($this->categoryTree($locale), $slug);
    }

    public function searchProducts(ProductFilters $filters, string $locale): array
    {
        $query = $this->toSearchQuery($filters, $locale);
        $result = $this->products->list($query, $locale);
        $facets = [
            'color_families' => $result['facets']['color_families'],
            'attributes' => $this->attributeFacets($result['facets']['attribute_values'], $locale),
            'brands' => $this->brandFacets($result['facets']['brands'] ?? []),
            'sizes' => $this->sizeFacets($result['facets']['sizes'] ?? []),
            'price' => $result['facets']['price'] ?? null,
        ];

        return vani_filter('vani.service.catalog.search', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $query->page,
            'per_page' => $query->limit(),
            'facets' => $facets,
            'filters' => $this->filters($facets, $locale),
        ], $filters, $locale);
    }

    public function suggestions(string $text, string $locale, int $now, int $products = 5): array
    {
        $needle = self::fold($text);
        if (mb_strlen($needle) < 2) {
            return ['products' => [], 'categories' => [], 'brands' => []];
        }

        $found = $this->products->list($this->toSearchQuery(new ProductFilters(now: $now, text: $text, perPage: max(1, min(10, $products))), $locale), $locale);
        $categories = [];
        $walk = function (array $nodes) use (&$walk, &$categories, $needle): void {
            foreach ($nodes as $node) {
                if (count($categories) < 3 && str_contains(self::fold((string) $node['name']), $needle)) {
                    $categories[] = ['id' => (int) $node['id'], 'slug' => (string) $node['slug'], 'name' => (string) $node['name']];
                }
                $walk($node['children']);
            }
        };
        $walk($this->categoryTree($locale));
        $brands = array_slice(array_filter($this->brands(), fn (array $brand): bool => str_contains(self::fold($brand['name']), $needle)), 0, 3);

        return vani_filter('vani.service.catalog.suggestions', [
            'products' => array_map(fn (array $item): array => ['id' => (int) $item['id'], 'slug' => (string) $item['slug'], 'name' => $item['name'], 'image_url' => $item['image_url']], $found['items']),
            'categories' => $categories,
            'brands' => array_map(fn (array $brand): array => ['slug' => $brand['slug'], 'name' => $brand['name'], 'logo_url' => $brand['logo_url']], $brands),
        ], $text, $locale);
    }

    public function brands(): array
    {
        return vani_filter('vani.service.catalog.brands', Brand::query()->where('status', Brand::ACTIVE)->orderBy('position')->orderBy('name')->get()
            ->map(fn (Brand $brand): array => $this->brandView($brand))->all());
    }

    public function brand(string $slug): ?array
    {
        $brand = Brand::query()->where('slug', $slug)->where('status', Brand::ACTIVE)->first();

        return $brand === null ? null : vani_filter('vani.service.catalog.brand', [...$this->brandView($brand), 'meta_title' => $brand->meta_title, 'meta_description' => $brand->meta_description], $slug);
    }

    public function productsByIds(array $styleIds, string $locale, int $now): array
    {
        return vani_filter('vani.service.catalog.products_by_ids', $this->products->byIds($styleIds, $locale, $now), $locale);
    }

    public function productDetail(string $slug, string $locale, int $now): ?array
    {
        $detail = $this->products->detail($slug, $locale, $now);

        return $detail === null ? null : vani_filter('vani.service.catalog.product_detail', $detail, $slug, $locale);
    }

    public function sellableVariants(array $variantIds, string $locale, int $now): array
    {
        return $variantIds === [] ? [] : $this->products->sellable($variantIds, $locale, $now);
    }

    /**
     * Chuẩn hoá để so khớp tên: chữ thường, bỏ dấu tiếng Việt (đ → d).
     */
    private static function fold(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }

    /**
     * Slug/mã không tồn tại trong phạm vi → lọc ra rỗng (không âm thầm bỏ bộ lọc).
     */
    private function toSearchQuery(ProductFilters $filters, string $locale): ProductSearchQuery
    {
        $categoryId = $filters->categorySlug === null ? null
            : (Category::query()->whereLocalizedSlug($filters->categorySlug, $locale)->where('status', 'active')->orderBy('id')->value('id') ?? -1);
        $collectionId = $filters->collectionSlug === null ? null
            : (ProductCollection::query()->whereLocalizedSlug($filters->collectionSlug, $locale)->where('status', 'active')->orderBy('id')->value('id') ?? -1);

        $brandIds = $filters->brandSlugs === [] ? []
            : (Brand::query()->whereIn('slug', $filters->brandSlugs)->where('status', Brand::ACTIVE)->pluck('id')->map(fn ($id): int => (int) $id)->all() ?: [-1]);

        $attributeValueIds = [];
        foreach ($filters->attributes as $code => $valueCodes) {
            $attribute = Attribute::query()->with('values')->where('code', $code)->where('kind', 'spec')->first();
            $ids = $attribute?->values->whereIn('code', $valueCodes)->pluck('id')->all() ?? [];
            $attributeValueIds[$attribute->id ?? -1] = $ids === [] ? [-1] : $ids;
        }

        return new ProductSearchQuery(
            brandIds: $brandIds,
            now: $filters->now,
            text: $filters->text,
            categoryId: $categoryId,
            collectionId: $collectionId,
            colorFamilies: array_values(array_filter($filters->colorFamilies, fn (string $family): bool => ColorFamily::tryFrom($family) !== null)),
            attributeValueIds: $attributeValueIds,
            sort: in_array($filters->sort, ProductSearchQuery::SORTS, true) ? $filters->sort : ProductSearchQuery::SORT_NEWEST,
            page: $filters->page,
            perPage: $filters->perPage,
            priceMin: $filters->priceMin,
            priceMax: $filters->priceMax,
            sizeCodes: array_values(array_unique(array_filter($filters->sizeCodes, fn (string $code): bool => $code !== '' && mb_strlen($code) <= 16))),
        );
    }

    /**
     * Bộ lọc để hiển thị theo cấu hình Admin (§3.5-1): đúng thứ tự, bỏ facet tắt hoặc không có giá trị trong kết quả.
     * `param` là khoá query của Storefront API / trang danh sách (`brand`, `color`, `attr.<mã>` = `attr[<mã>]`).
     *
     * @param  array{color_families: array<string, int>, attributes: list<array<string, mixed>>, brands: list<array<string, mixed>>, sizes: list<array{code: string, count: int}>, price: array{min: int, max: int}|null}  $facets
     * @return list<array{key: string, param: string, label: string, display: string, options: list<array{value: string, label: string, count: int, swatch?: string}>, range?: array{min: int, max: int}}>
     */
    private function filters(array $facets, string $locale): array
    {
        $attributes = collect($facets['attributes'])->keyBy('code');
        $filters = [];
        foreach ($this->facetSettings->configured($locale) as $facet) {
            if (! $facet['enabled']) {
                continue;
            }
            if ($facet['type'] === 'price') {
                // Khoảng giá: không có lựa chọn rời, client hiện hai ô nhập (`price_min`, `price_max`) trong `range`.
                if ($facets['price'] !== null && $facets['price']['min'] < $facets['price']['max']) {
                    $filters[] = ['key' => 'price', 'param' => 'price', 'label' => $facet['label'], 'display' => 'range', 'options' => [], 'range' => $facets['price']];
                }

                continue;
            }
            [$param, $options] = match ($facet['type']) {
                'brand' => ['brand', array_map(fn (array $brand): array => ['value' => (string) $brand['slug'], 'label' => (string) $brand['name'], 'count' => (int) $brand['count']], $facets['brands'])],
                'color' => ['color', array_map(fn (string $family, int $count): array => [
                    'value' => $family, 'label' => __("catalog::labels.color_family.{$family}", [], $locale), 'count' => $count, 'swatch' => ColorFamily::tryFrom($family)?->swatch() ?? '#e5e7eb',
                ], array_keys($facets['color_families']), array_values($facets['color_families']))],
                'size' => ['size', array_map(fn (array $size): array => ['value' => $size['code'], 'label' => $size['code'], 'count' => $size['count']], $facets['sizes'])],
                default => ["attr.{$facet['code']}", array_map(fn (array $value): array => ['value' => (string) $value['code'], 'label' => (string) ($value['label'] ?? $value['code']), 'count' => (int) $value['count']], $attributes[$facet['code']]['values'] ?? [])],
            };
            if ($options !== []) {
                $filters[] = ['key' => $facet['key'], 'param' => $param, 'label' => $facet['label'], 'display' => $facet['display'], 'options' => $options];
            }
        }

        return $filters;
    }

    /**
     * Size có sản phẩm trong kết quả, theo thứ tự hiển thị của size (S → M → L, 36 → 37 → 38).
     *
     * @param  array<string, int>  $counts  mã size => số sản phẩm
     * @return list<array{code: string, count: int}>
     */
    private function sizeFacets(array $counts): array
    {
        if ($counts === []) {
            return [];
        }

        $order = Size::query()->whereIn('code', array_map('strval', array_keys($counts)))->orderBy('size_system')->orderBy('sort_order')->pluck('code')->unique()->values()->all();

        return array_map(fn (string $code): array => ['code' => $code, 'count' => (int) $counts[$code]], array_map('strval', $order));
    }

    /**
     * @param  array<int, int>  $counts
     * @return list<array<string, mixed>>
     */
    private function attributeFacets(array $counts, string $locale): array
    {
        if ($counts === []) {
            return [];
        }

        return AttributeValue::query()
            ->with(['translations', 'attribute.translations'])
            ->whereIn('id', array_keys($counts))
            ->orderBy('position')
            ->get()
            ->groupBy('attribute_id')
            ->map(fn ($values): array => [
                'code' => $values->first()->attribute->code,
                'name' => $values->first()->attribute->translate('name', $locale),
                'values' => $values->map(fn (AttributeValue $value): array => [
                    'code' => $value->code,
                    'label' => $value->translate('label', $locale),
                    'count' => $counts[$value->id] ?? 0,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function present(array $nodes): array
    {
        return array_map(fn (array $node): array => [
            'id' => $node['id'],
            'slug' => $node['slug'],
            'slugs' => $node['slugs'],
            'locales' => $node['locales'],
            'name' => $node['name'],
            'description' => $node['description'],
            'image_url' => $node['image_url'],
            'children' => $this->present($node['children']),
        ], $nodes);
    }

    /**
     * Tìm danh mục trong cây; kèm `breadcrumb` = đường từ gốc tới chính nó (cùng dạng breadcrumb của PDP).
     *
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array{id: int, slug: string, name: string}>  $trail
     * @return array<string, mixed>|null
     */
    private function find(array $nodes, string $slug, array $trail = []): ?array
    {
        foreach ($nodes as $node) {
            $path = [...$trail, ['id' => $node['id'], 'slug' => $node['slug'], 'name' => $node['name']]];
            // Slug theo ngôn ngữ hoặc slug gốc (link cũ/chưa có slug riêng — trang chuyển hướng 301 sang slug riêng).
            if ($node['slug'] === $slug || ($node['slugs'][StoreLocale::default()] ?? null) === $slug) {
                return [...$node, 'breadcrumb' => $path];
            }
            $found = $this->find($node['children'], $slug, $path);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @return array{id: int, code: string, slug: string, name: string, description: ?string, logo_url: ?string}
     */
    private function brandView(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'code' => $brand->code,
            'slug' => $brand->slug,
            'name' => $brand->name,
            'description' => $brand->description,
            // Logo từ Thư viện ảnh (bản thu nhỏ 400px); `logo_path` là file cũ (trước 0.3.90).
            'logo_url' => $brand->logo_media_id !== null ? app(MediaUrls::class)->url($brand->logo_media_id, 400) : ($brand->logo_path === null ? null : Storage::disk((string) config('vanishop.media.disk', 'public'))->url($brand->logo_path)),
        ];
    }

    /**
     * @param  array<int, int>  $counts  brand id => số sản phẩm
     * @return list<array{slug: string, name: string, count: int}>
     */
    private function brandFacets(array $counts): array
    {
        if ($counts === []) {
            return [];
        }

        return Brand::query()->whereIn('id', array_keys($counts))->where('status', Brand::ACTIVE)->orderBy('position')->orderBy('name')->get()
            ->map(fn (Brand $brand): array => ['slug' => $brand->slug, 'name' => $brand->name, 'count' => $counts[$brand->id] ?? 0])
            ->all();
    }
}
