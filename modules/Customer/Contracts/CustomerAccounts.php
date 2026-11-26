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
     * Gửi mã một lần để xác nhận xoá tài khoản (0.5.96, reference-parity L40) — qua email trên hồ sơ (thư riêng cho việc xoá
     * tài khoản) hoặc kênh OTP của plugin (SMS/ZNS) khi chưa có email. Còn việc dở → `customer.deletion_blocked`; không có
     * kênh gửi (chưa có email, không plugin SMS/ZNS) → `customer.otp_unavailable` (dùng mật khẩu hoặc liên hệ cửa hàng).
     *
     * @return array{channel: string, expires_in: int}
     */
    public function requestDeletionCode(int $customerId, string $ip): array;

    /**
     * Xoá tài khoản = ẩn danh hoá (giữ đơn theo nghĩa vụ kế toán), thu hồi mọi phiên. Xác nhận bằng mật khẩu hiện tại hoặc mã
     * từ `requestDeletionCode()` (0.5.96). Còn việc dở → `customer.deletion_blocked`; sai mật khẩu → `customer.password_mismatch`;
     * sai/hết hạn mã → `customer.otp_invalid`; không gửi cả hai → `customer.password_required`.
     */
    public function deleteAccount(int $customerId, ?string $password = null, ?string $code = null): void;

    /**
     * Mọi đồng ý nhận tin của khách (đang bật và đã huỷ), theo kênh.
     *
     * @return list<array{channel: string, purpose: string, granted: bool, granted_at: ?string, revoked_at: ?string, source: string}>
     */
    public function marketingConsents(int $customerId): array;

    /** Khách đang đồng ý nhận tin marketing qua kênh (`email`, `sms`, `zns`…). */
    public function marketingConsent(int $customerId, string $channel): bool;

    /**
     * Bật/tắt đồng ý nhận tin marketing qua kênh; ghi lịch sử kèm nguồn (`storefront:account`, `storefront:checkout`…).
     */
    public function setMarketingConsent(int $customerId, string $channel, bool $granted, string $source, ?string $ip = null): void;

    /**
     * Link huỷ nhận tin marketing của kênh, đặt trong mọi tin marketing (Nghị định 91/2020). Link có chữ ký, không hết hạn,
     * dùng mã công khai của khách; mở link là huỷ ngay, không cần đăng nhập.
     */
    public function unsubscribeUrl(int $customerId, string $channel): string;

    /**
     * Huỷ đồng ý theo link trong email (đã kiểm chữ ký ở route). Khách không tồn tại → false.
     */
    public function unsubscribe(string $customerPublicId, string $channel, ?string $ip = null): bool;
}
