<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

/**
 * Service contract: địa chỉ giao hàng theo danh mục địa giới đang dùng (AddressDirectory đầu tiên đang bật).
 * Module khác (sổ địa chỉ khách, Admin sửa địa chỉ đơn, storefront) dùng cùng quy tắc với checkout.
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
