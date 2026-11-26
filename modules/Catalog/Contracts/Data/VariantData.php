<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * SKU cho module khác. `options`: tổ hợp tuỳ chọn theo thứ tự trục (ADR-045) — {type: mã loại, name: tên loại, code: mã giá
 * trị, value: tên giá trị}; hiển thị bằng VariantOptionsText.
 */
#[PluginApi]
final readonly class VariantData
{
    /**
     * @param  list<array{type: string, name: string, code: string, value: string}>  $options
     */
    public function __construct(
        public int $id,
        public ?int $brandId,
        public int $productId,
        public string $productCode,
        public string $productName,
        public string $sku,
        public string $status,
        public array $options = [],
        /** false = sản phẩm không theo dõi tồn: luôn bán được, Inventory tự bù tồn khi giữ hàng (0.4.48). */
        public bool $tracksInventory = true,
    ) {}
}
