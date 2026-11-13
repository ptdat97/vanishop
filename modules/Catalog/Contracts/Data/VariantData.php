<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * SKU cho module khác. `options`: tổ hợp tuỳ chọn theo thứ tự trục (ADR-045) — {type: mã loại, name: tên loại, code: mã giá
 * trị, value: tên giá trị}; hiển thị bằng VariantOptionsText.
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
        public string $status,
        public array $options = [],
    ) {}
}
