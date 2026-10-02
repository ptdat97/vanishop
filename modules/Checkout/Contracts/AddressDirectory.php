<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

/**
 * Extension point (tag `vani.checkout.address_directories`): danh mục địa giới hành chính cho địa chỉ giao hàng.
 * Có implementation (vd. plugin hệ thống `vani.provinces-vn`) → Core kiểm tra mã tỉnh/phường khi đặt hàng, chụp tên
 * chuẩn vào đơn, Storefront API + checkout native cho chọn tỉnh/phường. Không có → địa chỉ nhập tự do.
 * Dùng implementation đầu tiên đang bật. Dữ liệu tĩnh/cache — không I/O mạng (chạy trong transaction đặt hàng).
 */
interface AddressDirectory
{
    public const TAG = 'vani.checkout.address_directories';

    public function code(): string;

    /**
     * @return list<array{code: string, name: string}>
     */
    public function provinces(): array;

    /**
     * @return list<array{code: string, name: string}> rỗng nếu không có tỉnh
     */
    public function wards(string $provinceCode): array;

    /**
     * @return array{code: string, name: string}|null
     */
    public function province(string $provinceCode): ?array;

    /**
     * Phường/xã thuộc đúng tỉnh; sai tỉnh → null.
     *
     * @return array{code: string, name: string}|null
     */
    public function ward(string $provinceCode, string $wardCode): ?array;
}
