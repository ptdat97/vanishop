<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderLineData
{
    public function __construct(
        public int $id,
        public int $variantId,
        public string $sku,
        public string $productName,
        public int $quantity,
        /** Thành tiền dòng sau giảm giá phân bổ — cơ sở tính tiền hoàn khi trả hàng. */
        public int $totalAmount = 0,
        public ?string $colorName = null,
        public string $sizeCode = '',
        public ?string $brandName = null,
        /** @var array<string, array<string, scalar|null>> */
        public array $options = [],
    ) {}
}
