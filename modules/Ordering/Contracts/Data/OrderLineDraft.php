<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderLineDraft
{
    public function __construct(
        public int $variantId,
        public string $sku,
        public string $productName,
        public ?string $colorName,
        public string $sizeCode,
        public ?string $imageUrl,
        public int $quantity,
        public int $unitAmount,
        public ?int $compareAtAmount,
        public int $subtotalAmount,
        public int $discountAmount,
        public int $totalAmount,
        public int $taxRateBp,
        public int $taxAmount,
        /** Thương hiệu của sản phẩm lúc đặt (snapshot, báo cáo theo brand). */
        public ?int $brandId = null,
        public ?string $brandName = null,
    ) {}
}
