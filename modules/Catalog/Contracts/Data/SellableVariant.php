<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Variant đang bán được, kèm thông tin hiển thị tối thiểu cho dòng giỏ/đơn. `options`: tổ hợp tuỳ chọn theo thứ tự trục
 * (ADR-045); `colorCode` / `colorName` / `sizeCode` tạm giữ trong lúc chuyển (flexible-variants F3).
 */
final readonly class SellableVariant
{
    /**
     * @param  list<array{type: string, name: string, code: string, value: string}>  $options
     */
    public function __construct(
        public int $id,
        public ?int $brandId,
        public int $styleId,
        public string $sku,
        public string $slug,
        public string $name,
        public string $colorCode,
        public ?string $colorName,
        public string $sizeCode,
        public ?string $imageUrl,
        public ?string $brandName = null,
        /** Khối lượng một sản phẩm (gram, 0.3.73); null = chưa nhập. */
        public ?int $weightGram = null,
        public array $options = [],
    ) {}
}
