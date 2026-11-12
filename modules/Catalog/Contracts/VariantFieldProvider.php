<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Extension\Contracts\Data\FieldDefinition;

/**
 * Extension point (docs/03-domains/variant-matrix.md §3.3): góp **cột dữ liệu theo SKU** vào ma trận biến thể của Admin
 * (giá, tồn, trường riêng của plugin) mà Catalog không phụ thuộc module/plugin đó. Đăng ký qua tag `TAG` (Core:
 * `Extensions::tag()`, plugin: `contribute()`).
 *
 * Giá trị trên màn hình và khi lưu có khoá `<code()>.<field key>`. Lỗi của một nhà cung cấp được cô lập: cột không hiện,
 * phần còn lại vẫn chạy. Contract test: `Modules\Catalog\Testing\VariantFieldProviderContract`.
 */
interface VariantFieldProvider
{
    public const TAG = 'vani.catalog.variant_fields';

    /** Khoá ổn định (vd. `pricing`, `inventory`, `vani-size-chart`) — tiền tố của khoá trường. */
    public function code(): string;

    /** Tên nhóm cột hiển thị trong Admin. */
    public function label(): string;

    /**
     * Trường theo SKU. Danh sách rỗng = không áp dụng cho cửa hàng hiện tại (vd. tồn khi có nhiều kho).
     *
     * @return list<FieldDefinition>
     */
    public function fields(): array;

    /** Quyền cần để sửa cột; thiếu quyền thì cột chỉ đọc. null = theo quyền sửa sản phẩm (`catalog.manage`). */
    public function permission(): ?string;

    /**
     * Giá trị hiện tại cho các SKU của sản phẩm.
     *
     * @param  list<int>  $variantIds
     * @return array<int, array<string, scalar|null>> variant id => field key => giá trị (thiếu = chưa có)
     */
    public function values(int $styleId, array $variantIds): array;

    /**
     * Lưu giá trị đã kiểm tra theo `fields()` (chỉ gửi SKU và trường có thay đổi). Lỗi theo SKU trả về để Admin hiện ở dòng
     * đó (không ném lỗi cho cả màn hình).
     *
     * @param  array<int, array<string, scalar|null>>  $values  variant id => field key => giá trị
     * @return array<int, string> variant id => thông báo lỗi
     */
    public function save(int $styleId, array $values): array;

    /**
     * Dữ liệu theo SKU công bố ra storefront (`variants[].extensions.<code()>` của Storefront API). Không công bố gì → [].
     * Không đưa dữ liệu nội bộ (giá vốn, số tồn chính xác…).
     *
     * @param  list<int>  $variantIds
     * @return array<int, array<string, mixed>>
     */
    public function storefront(array $variantIds): array;
}
