<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\VariantData;

/**
 * Service contract: tra variant cho các module khác (Pricing, Inventory, Ordering…).
 */
interface VariantDirectory
{
    /**
     * Mọi variant (cả ngừng bán) của một style, theo thứ tự màu → size.
     *
     * @return list<VariantData>
     */
    public function ofStyleCode(string $styleCode): array;

    /**
     * @param  list<int>  $variantIds
     * @return array<int, VariantData> id => data (id không tồn tạithì không có mặt)
     */
    public function find(array $variantIds): array;

    /**
     * Tra theo SKU (mã duy nhất toàn hệ thống) — dùng cho đồng bộ từ hệ thống ngoài.
     *
     * @param  list<string>  $skus
     * @return array<string, VariantData> sku => data (SKU không tồn tạithì không có mặt)
     */
    public function findBySkus(array $skus): array;

    /**
     * Tìm variant (cả ngừng bán) theo SKU, mã sản phẩm hoặc tên sản phẩm (0.3.83 — lưới tồn kho Admin); chuỗi rỗng = mọi
     * variant. Theo thứ tự tên sản phẩm → màu → size, tối đa `$limit`.
     *
     * @return list<VariantData>
     */
    public function search(string $text, int $limit = 2000): array;
}
