<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * SKU cho module khác. `options`: tổ hợp tuỳ chọn theo thứ tự trục (ADR-045). `colorCode` / `sizeCode`: mã giá trị của loại
 * `color` / `size` ('' nếu sản phẩm không có trục đó) — tạm giữ trong lúc chuyển sang `options` (flexible-variants F3).
 */
final readonly class VariantData
{
    /**
     * @param  list<array{type: string, name: string, code: string, value: string}>  $options
     */
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
        public array $options = [],
    ) {}
}
