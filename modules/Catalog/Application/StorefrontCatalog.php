<?php

declare(strict_types=1);

namespace Modules\Catalog\Application;

use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Application\Products\StorefrontProductQuery;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\AttributeValue;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;

/**
 * Hiện thực CatalogReader cho storefront (native và Storefront API).
 */
final class StorefrontCatalog implements CatalogReader
{
    public function __construct(
        private readonly CategoryTreeQuery $categories,
        private readonly StorefrontProductQuery $products,
    ) {}

    public function categoryTree(string $locale): array
    {
        return $this->present($this->categories->tree(activeOnly: true, locale: $locale));
    }

    public function category(string $slug, string $locale): ?array
    {
        return $this->find($this->categoryTree($locale), $slug);
    }

    public function searchProducts(ProductFilters $filters, string $locale): array
    {
        $query = $this->toSearchQuery($filters);
        $result = $this->products->list($query, $locale);

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $query->page,
            'per_page' => $query->limit(),
            'facets' => [
                'color_families' => $result['facets']['color_families'],
                'attributes' => $this->attributeFacets($result['facets']['attribute_values'], $locale),
                'brands' => $this->brandFacets($result['facets']['brands'] ?? []),
            ],
        ];
    }

    public function brands(): array
    {
        return Brand::query()->where('status', Brand::ACTIVE)->orderBy('position')->orderBy('name')->get()
            ->map(fn (Brand $brand): array => $this->brandView($brand))->all();
    }

    public function brand(string $slug): ?array
    {
        $brand = Brand::query()->where('slug', $slug)->where('status', Brand::ACTIVE)->first();

        return $brand === null ? null : [...$this->brandView($brand), 'meta_title' => $brand->meta_title, 'meta_description' => $brand->meta_description];
    }

    public function productDetail(string $slug, string $locale, int $now): ?array
    {
        return $this->products->detail($slug, $locale, $now);
    }

    public function sellableVariants(array $variantIds, string $locale, int $now): array
    {
        return $variantIds === [] ? [] : $this->products->sellable($variantIds, $locale, $now);
    }

    /**
     * Slug/mã không tồn tại trong phạm vi → lọc ra rỗng (không âm thầm bỏ bộ lọc).
     */
    private function toSearchQuery(ProductFilters $filters): ProductSearchQuery
    {
        $categoryId = $filters->categorySlug === null ? null
            : (Category::query()->where('slug', $filters->categorySlug)->where('status', 'active')->orderBy('id')->value('id') ?? -1);
        $collectionId = $filters->collectionSlug === null ? null
            : (ProductCollection::query()->where('slug', $filters->collectionSlug)->where('status', 'active')->orderBy('id')->value('id') ?? -1);

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
            sort: $filters->sort,
            page: $filters->page,
            perPage: $filters->perPage,
        );
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
            'name' => $node['name'],
            'description' => $node['description'],
            'image_url' => $node['image_url'],
            'children' => $this->present($node['children']),
        ], $nodes);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, mixed>|null
     */
    private function find(array $nodes, string $slug): ?array
    {
        foreach ($nodes as $node) {
            if ($node['slug'] === $slug) {
                return $node;
            }
            $found = $this->find($node['children'], $slug);
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
            'logo_url' => $brand->logo_path === null ? null : Storage::disk((string) config('vanishop.media.disk', 'public'))->url($brand->logo_path),
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
