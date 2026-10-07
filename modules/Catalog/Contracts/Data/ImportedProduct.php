<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

final readonly class ImportedProduct
{
    /**
     * @param  list<string>  $skus  mọi SKU của sản phẩm (cũ + mới)
     */
    public function __construct(
        public int $styleId,
        public string $slug,
        public bool $created,
        public int $imagesAdded,
        public array $skus,
    ) {}
}
