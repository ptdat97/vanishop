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
     * Mọi variant (cả ngừng bán) của một style, theo thứ tự giá trị của từng trục.
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
     * Variant (cả ngừng bán) theo style id (0.3.103 — thao tác hàng loạt trên danh sách sản phẩm: đổi giá, tồn).
     *
     * @param  list<int>  $styleIds
     * @return array<int, list<VariantData>> style id => variant theo thứ tự giá trị của từng trục (style không có variant thì không có mặt)
     */
    public function ofStyles(array $styleIds): array;

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
     * Chỉ mục nhẹ cho màn hình làm việc trên mọi variant (lưới tồn Admin, P2 A1): tìm theo SKU/mã/tên sản phẩm (chuỗi rỗng =
     * mọi variant, cả ngừng bán), hoặc đúng một mã sản phẩm. Không giới hạn số dòng — chỉ có khoá và mã, đọc thêm bằng find()
     * cho phần đang hiển thị. Theo thứ tự tên sản phẩm → mã sản phẩm → giá trị từng trục (thứ tự trong sản phẩm).
     * `options`: mã loại tuỳ chọn => mã giá trị của SKU (ADR-045).
     *
     * @return list<array{id: int, style_id: int, style_code: string, options: array<string, string>, status: string}>
     */
    public function searchIndex(string $text = '', ?string $styleCode = null): array;

    /**
     * Variant thuộc sản phẩm không theo dõi tồn (0.4.48) — một truy vấn nhẹ cho tính ATS trên danh sách/giỏ.
     *
     * @param  list<int>  $variantIds
     * @return list<int>
     */
    public function untrackedInventory(array $variantIds): array;

    /**
     * Loại tuỳ chọn biến thể (ADR-045) theo thứ tự: mã => tên theo ngôn ngữ mặc định — nhãn cho bộ lọc theo tuỳ chọn.
     *
     * @return array<string, string>
     */
    public function optionTypes(): array;
}
