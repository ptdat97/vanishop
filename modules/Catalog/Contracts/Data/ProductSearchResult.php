<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

final readonly class ProductSearchResult
{
    /**
     * @param  list<int>  $styleIds  theo thứ tự hiển thị
     * @param  array{color_families: array<string, int>, attribute_values: array<int, int>}  $facets
     */
    public function __construct(
        public array $styleIds,
        public int $total,
        public array $facets = ['color_families' => [], 'attribute_values' => []],
    ) {}
}
