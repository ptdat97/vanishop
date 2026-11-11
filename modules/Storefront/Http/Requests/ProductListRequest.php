<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Tenancy\Contracts\Settings;

/**
 * GET /products?q=&category=&collection=&brand=urbanx,lumiere&color=white,black&size=S,M&price_min=200000&price_max=500000&attr[material]=silk,linen&sort=&page=&per_page=
 * GET /products?ids=12,7,30 — thẻ sản phẩm theo id (tối đa 30, giữ thứ tự, bỏ qua các bộ lọc khác; 0.3.43).
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
            'brand' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:200'],
            'size' => ['nullable', 'string', 'max:200'],
            'attr' => ['nullable', 'array', 'max:20'],
            'attr.*' => ['string', 'max:500'],
            // Khoảng giá niêm yết, đơn vị nhỏ nhất của tiền tệ cửa hàng (VND: đồng).
            'price_min' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'price_max' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'sort' => ['nullable', Rule::in(ProductSearchQuery::SORTS)],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ProductSearchQuery::MAX_PER_PAGE],
            'ids' => ['nullable', 'string', 'regex:/^[1-9]\d{0,9}(,[1-9]\d{0,9}){0,29}$/'],
        ];
    }

    /**
     * @return list<int>|null null = không lọc theo id
     */
    public function styleIds(): ?array
    {
        return $this->filled('ids') ? array_map('intval', explode(',', (string) $this->input('ids'))) : null;
    }

    public function toFilters(int $now): ProductFilters
    {
        $split = fn (string $value): array => array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $part): bool => $part !== ''));

        $attributes = [];
        foreach ((array) $this->input('attr', []) as $code => $values) {
            $attributes[(string) $code] = $split((string) $values);
        }

        return new ProductFilters(
            now: $now,
            text: (string) $this->input('q', ''),
            categorySlug: $this->filled('category') ? (string) $this->input('category') : null,
            collectionSlug: $this->filled('collection') ? (string) $this->input('collection') : null,
            colorFamilies: $split((string) $this->input('color', '')),
            attributes: $attributes,
            sort: (string) $this->input('sort', ProductSearchQuery::SORT_NEWEST),
            page: (int) $this->input('page', 1),
            // Không truyền per_page → cấu hình cửa hàng `storefront.per_page` (12–60).
            perPage: (int) $this->input('per_page', $this->container->make(Settings::class)->get('core', 'storefront.per_page', 24)),
            brandSlugs: $split((string) $this->input('brand', '')),
            sizeCodes: $split((string) $this->input('size', '')),
            priceMin: $this->filled('price_min') ? (int) $this->input('price_min') : null,
            priceMax: $this->filled('price_max') ? (int) $this->input('price_max') : null,
        );
    }
}
