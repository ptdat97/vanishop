<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

/**
 * Extension point (0.3.42): luật số điện thoại của thị trường — chuẩn hoá chuỗi người dùng nhập về E.164 và dạng hiển
 * thị trong nước. Bắt buộc đúng 1, chọn qua `vanishop.locale.phone_policy`; Core chỉ có `international` (nhận số đã
 * ở dạng quốc tế `+…`/`00…`), luật Việt Nam là plugin hệ thống `vani.phone-vn`.
 *
 * Gọi qua `Modules\Shared\Support\Phones` (khách đăng nhập OTP, checkout, tra cứu đơn, sổ địa chỉ). Không gọi mạng.
 */
interface PhoneNumberPolicy
{
    public const TAG = 'vani.phone.policies';

    public function code(): string;

    /**
     * E.164 (`+` + 7–15 chữ số, không bắt đầu bằng 0) hoặc null khi không phải số hợp lệ của thị trường.
     */
    public function normalize(string $input): ?string;

    /**
     * Dạng hiển thị trong nước của số đã chuẩn hoá (vd. VN: `0912345678`); thị trường không có dạng riêng → E.164.
     */
    public function national(string $e164): string;
}
