<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

final readonly class VariantData
{
    public function __construct(
        public int $id,
        public ?int $brandId,
        public int $styleId,
        public string $styleCode,
        public string $styleName,
        public string $sku,
        public string $colorCode,
        public string $sizeCode,
        public string $status,
    ) {}
}
