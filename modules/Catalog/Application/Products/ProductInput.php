<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use DateTimeImmutable;
use Modules\Catalog\Domain\StyleStatus;

final readonly class ProductInput
{
    /**
     * @param  array<string, array<string, string|null>>  $translations  locale => fields
     * @param  list<int>  $categoryIds
     * @param  array<int, mixed>  $attributes  attribute id => int | list<int> | string | bool | null
     */
    public function __construct(
        public string $styleCode,
        public string $slug,
        public StyleStatus $status,
        public array $translations,
        public ?DateTimeImmutable $publishedFrom = null,
        public ?DateTimeImmutable $publishedTo = null,
        public array $categoryIds = [],
        public ?int $primaryCategoryId = null,
        public array $attributes = [],
        public ?int $brandId = null,
    ) {}
}
