<?php

declare(strict_types=1);

namespace Plugin\SearchMeilisearch\Infrastructure;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Contracts\ConfigurableSearchIndex;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Meilisearch qua REST API (không cần SDK). Một index cho mọi brand, lọc bằng brand_id.
 * Cấu hình index (filterable/sortable attributes) được đặt bằng lệnh vani:search:setup.
 */
final class MeilisearchSearchProvider implements ConfigurableSearchIndex, SearchProvider
{
    public const FILTERABLE = ['brand_id', 'status', 'published_from', 'published_to', 'category_ids', 'collection_ids', 'color_families', 'attribute_value_ids'];

    public const SORTABLE = ['created_at', 'style_code'];

    public function __construct(
        private readonly string $host,
        private readonly ?string $key,
        private readonly string $index,
    ) {}

    public function code(): string
    {
        return 'meilisearch';
    }

    public function index(ProductDocument $document): void
    {
        $this->client()->post("indexes/{$this->index}/documents?primaryKey=id", [$document->toArray()])->throw();
    }

    public function remove(int $styleId): void
    {
        $this->client()->delete("indexes/{$this->index}/documents/{$styleId}")->throw();
    }

    public function search(ProductSearchQuery $query): ProductSearchResult
    {
        $response = $this->client()->post("indexes/{$this->index}/search", [
            'q' => VietnameseText::normalize($query->text),
            'filter' => $this->filters($query),
            'facets' => ['color_families', 'attribute_value_ids'],
            'sort' => $query->sort === ProductSearchQuery::SORT_CODE ? ['style_code:asc'] : ['created_at:desc'],
            'offset' => $query->offset(),
            'limit' => $query->limit(),
            'attributesToRetrieve' => ['id'],
        ])->throw()->json();

        $distribution = (array) ($response['facetDistribution'] ?? []);

        return new ProductSearchResult(
            styleIds: array_map(fn (array $hit): int => (int) $hit['id'], (array) ($response['hits'] ?? [])),
            total: (int) ($response['estimatedTotalHits'] ?? $response['totalHits'] ?? 0),
            facets: [
                'color_families' => array_map('intval', (array) ($distribution['color_families'] ?? [])),
                'attribute_values' => collect((array) ($distribution['attribute_value_ids'] ?? []))
                    ->mapWithKeys(fn ($total, $id): array => [(int) $id => (int) $total])->all(),
            ],
        );
    }

    /**
     * Cấu hình index: thuộc tính lọc/sắp xếp và trường tìm kiếm.
     */
    public function setupIndex(): void
    {
        $this->client()->post('indexes', ['uid' => $this->index, 'primaryKey' => 'id']);
        $this->client()->patch("indexes/{$this->index}/settings", [
            'filterableAttributes' => self::FILTERABLE,
            'sortableAttributes' => self::SORTABLE,
            'searchableAttributes' => ['search_text', 'style_code', 'names'],
        ])->throw();
    }

    /**
     * @return list<string|list<string>>
     */
    private function filters(ProductSearchQuery $query): array
    {
        $filters = [
            'brand_id IN ['.implode(', ', $query->brandIds ?: [0]).']',
            "status = 'active'",
            "(published_from IS NULL OR published_from <= {$query->now})",
            "(published_to IS NULL OR published_to > {$query->now})",
        ];

        if ($query->categoryId !== null) {
            $filters[] = "category_ids = {$query->categoryId}";
        }
        if ($query->collectionId !== null) {
            $filters[] = "collection_ids = {$query->collectionId}";
        }
        if ($query->colorFamilies !== []) {
            $filters[] = array_map(fn (string $family): string => "color_families = '".addslashes($family)."'", $query->colorFamilies);
        }
        foreach ($query->attributeValueIds as $valueIds) {
            if ($valueIds !== []) {
                $filters[] = array_map(fn (int $id): string => "attribute_value_ids = {$id}", $valueIds);
            }
        }

        return $filters;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->host, '/'))
            ->withToken((string) $this->key)
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->retry(2, 200, throw: false);
    }
}
