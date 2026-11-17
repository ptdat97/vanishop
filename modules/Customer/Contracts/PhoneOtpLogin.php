<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Shared\Contracts\PluginApi;

/**
 * Đăng nhập bằng OTP theo SĐT — phương thức **bổ sung**, do plugin bật (`vani.phone-otp-login`, Core 0.5.12). Core giữ khung
 * OTP (mã, hạn, giới hạn tần suất chống SMS pumping, kênh gửi `OtpSender`) và quy tắc tài khoản: SĐT chưa có hồ sơ → tạo;
 * hồ sơ khách vãng lai cùng SĐT → thành tài khoản, giữ lịch sử đơn. Plugin lo bề mặt (form, route, API riêng).
 *
 * Lỗi nghiệp vụ ném CustomerRejected (`customer.phone_invalid`, `customer.otp_invalid`, `customer.otp_rate_limited`,
 * `customer.otp_unavailable`).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface PhoneOtpLogin
{
    /**
     * Gửi mã đăng nhập. `$phone` dạng người dùng nhập (`0912…`, `+84 9…`).
     *
     * @return array{channel: string, expires_in: int}
     */
    public function requestCode(string $phone, string $ip): array;

    /**
     * Đúng mã → phiên mới (token Bearer mờ, chỉ trả một lần — ADR-024).
     *
     * @return array{customer: CustomerData, token: string}
     */
    public function login(string $phone, string $code, ?string $device = null): array;
}
