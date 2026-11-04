<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerData;

/**
 * Service contract: khách tự quản lý tài khoản (hồ sơ, mật khẩu, sổ địa chỉ, xoá tài khoản) — cho bề mặt ngoài Storefront API
 * (native storefront). Cùng quy tắc với `/me` của API. Lỗi nghiệp vụ ném CustomerRejected.
 */
interface CustomerAccounts
{
    /**
     * @param  array{full_name?: string, email?: string|null, birth_date?: string|null, gender?: string|null}  $attributes
     */
    public function updateProfile(int $customerId, array $attributes): CustomerData;

    /**
     * Đặt mật khẩu lần đầu (`$current` = null) hoặc đổi (phải đúng mật khẩu hiện tại).
     */
    public function setPassword(int $customerId, ?string $current, string $new): void;

    /**
     * @return array<string, mixed>|null địa chỉ thuộc khách, null nếu không có
     */
    public function address(int $customerId, int $addressId): ?array;

    /**
     * Có danh mục địa giới: mã tỉnh/phường phải hợp lệ (tên lấy theo danh mục), sai → `customer.address_invalid`.
     *
     * @param  array<string, mixed>  $data  label, full_name, phone, province_code, province_name?, ward_code, ward_name?, street_line, is_default?
     * @return array<string, mixed>
     */
    public function addAddress(int $customerId, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateAddress(int $customerId, int $addressId, array $data): array;

    public function deleteAddress(int $customerId, int $addressId): void;

    /**
     * Việc còn dở chặn xoá tài khoản (đơn chưa hoàn tất, đổi trả đang mở, việc do plugin báo qua `vani.customer.before_delete`).
     *
     * @return list<array{type: string, reference: string, label: string}>
     */
    public function deletionBlockers(int $customerId): array;

    /**
     * Gửi OTP xác nhận xoá tới SĐT của tài khoản. Còn việc dở → `customer.deletion_blocked` (không gửi OTP).
     *
     * @return array{channel: string, expires_in: int}
     */
    public function requestDeletionOtp(int $customerId, string $ip): array;

    /**
     * Xoá tài khoản = ẩn danh hoá (giữ đơn theo nghĩa vụ kế toán), thu hồi mọi phiên. Còn việc dở →
     * `customer.deletion_blocked`; sai OTP → `customer.otp_invalid`.
     */
    public function deleteAccount(int $customerId, string $code): void;
}
