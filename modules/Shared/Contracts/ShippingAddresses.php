<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Service contract: địa chỉ giao hàng theo danh mục địa giới đang dùng (AddressDirectory đầu tiên đang bật).
 * Checkout, sổ địa chỉ khách, Admin sửa địa chỉ đơn, storefront dùng cùng một quy tắc. Ở vòng 0 (Shared) để mọi module
 * thương mại dùng được mà không phụ thuộc nhau (architecture-consolidation M2).
 */
interface ShippingAddresses
{
    /** null = không có danh mục (địa chỉ nhập tự do). */
    public function directory(): ?AddressDirectory;

    /**
     * Có danh mục: `province_code`/`ward_code` phải hợp lệ và khớp nhau → tên lấy theo danh mục; sai → null.
     * Không có danh mục: trả nguyên địa chỉ.
     *
     * @param  array<string, string>  $address
     * @return array<string, string>|null
     */
    public function normalize(array $address): ?array;
}
