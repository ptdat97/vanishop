<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Categories;

use Modules\Catalog\Domain\CategoryStatus;

final readonly class CategoryInput
{
    /**
     * @param  array<string, array{name: string, description?: string|null, meta_title?: string|null, meta_description?: string|null}>  $translations
     */
    public function __construct(
        public string $slug,
        public CategoryStatus $status,
        public array $translations,
        public ?int $parentId = null,
        public int $position = 0,
    ) {}
}
