<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;

/**
 * GET /products?q=&category=&collection=&color=white,black&attr[material]=silk,linen&sort=&page=&per_page=
 */
final class ProductListRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:128'],
            'collection' => ['nullable', 'string', 'max:128'],
            'color' => ['nullable', 'string', 'max:200'],
            'attr' => ['nullable', 'array', 'max:20'],
            'attr.*' => ['string', 'max:500'],
            'sort' => ['nullable', Rule::in([ProductSearchQuery::SORT_NEWEST, ProductSearchQuery::SORT_CODE])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ProductSearchQuery::MAX_PER_PAGE],
        ];
    }

    /**
     * @param  list<int>  $brandIds
     */
    public function toFilters(array $brandIds, int $now): ProductFilters
    {
        $split = fn (string $value): array => array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $part): bool => $part !== ''));

        $attributes = [];
        foreach ((array) $this->input('attr', []) as $code => $values) {
            $attributes[(string) $code] = $split((string) $values);
        }

        return new ProductFilters(
            brandIds: $brandIds,
            now: $now,
            text: (string) $this->input('q', ''),
            categorySlug: $this->filled('category') ? (string) $this->input('category') : null,
            collectionSlug: $this->filled('collection') ? (string) $this->input('collection') : null,
            colorFamilies: $split((string) $this->input('color', '')),
            attributes: $attributes,
            sort: (string) $this->input('sort', ProductSearchQuery::SORT_NEWEST),
            page: (int) $this->input('page', 1),
            perPage: (int) $this->input('per_page', 24),
        );
    }
}
