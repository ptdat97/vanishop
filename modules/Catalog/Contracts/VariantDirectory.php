<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract: tra variant cho các module khác (Pricing, Inventory, Ordering…).
 */
#[PluginApi]
interface VariantDirectory
{
    /**
     * Mọi variant (cả ngừng bán) của một product, theo thứ tự giá trị của từng trục.
     *
     * @return list<VariantData>
     */
    public function ofProductCode(string $productCode): array;

    /**
     * @param  list<int>  $variantIds
     * @return array<int, VariantData> id => data (id không tồn tạithì không có mặt)
     */
    public function find(array $variantIds): array;

    /**
     * Variant (cả ngừng bán) theo product id (0.3.103 — thao tác hàng loạt trên danh sách sản phẩm: đổi giá, tồn).
     *
     * @param  list<int>  $productIds
     * @return array<int, list<VariantData>> product id => variant theo thứ tự giá trị của từng trục (product không có variant thì không có mặt)
     */
    public function ofProducts(array $productIds): array;

    /**
     * Tra theo SKU (mã duy nhất toàn hệ thống) — dùng cho đồng bộ từ hệ thống ngoài.
     *
     * @param  list<string>  $skus
     * @return array<string, VariantData> sku => data (SKU không tồn tạithì không có mặt)
     */
    public function findBySkus(array $skus): array;

    /**
     * Tìm variant (cả ngừng bán) theo SKU, mã sản phẩm hoặc tên sản phẩm (0.3.83 — lưới tồn kho Admin); chuỗi rỗng = mọi
     * variant. Theo thứ tự tên sản phẩm → giá trị từng trục, tối đa `$limit`.
     *
     * @return list<VariantData>
     */
    public function search(string $text, int $limit = 2000): array;

    /**
     * Variant thuộc sản phẩm không theo dõi tồn (0.4.48) — một truy vấn nhẹ cho tính ATS trên danh sách/giỏ.
     *
     * @param  list<int>  $variantIds
     * @return list<int>
     */
    public function untrackedInventory(array $variantIds): array;

    /**
     * Sản phẩm của từng SKU — một truy vấn nhẹ cho đếm sản phẩm trên danh sách lớn (không dựng VariantData).
     *
     * @param  list<int>  $variantIds
     * @return array<int, int> variant id => product id (SKU không tồn tại thì không có mặt)
     */
    public function productIdsOf(array $variantIds): array;

    /**
     * Loại tuỳ chọn biến thể (ADR-045) theo thứ tự: mã => tên theo ngôn ngữ mặc định — nhãn cho bộ lọc theo tuỳ chọn.
     *
     * @return array<string, string>
     */
    public function optionTypes(): array;
}
