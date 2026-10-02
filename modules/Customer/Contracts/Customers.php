<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerData;

/**
 * Service contract cho module khác (Checkout, Admin…).
 */
interface Customers
{
    public function find(int $customerId): ?CustomerData;

    /**
     * Khách vãng lai đặt hàng: tìm khách đang hoạt động theo SĐT, chưa có thì tạo profile ẩn. Không ghi đè
     * hồ sơ của khách đã đăng ký (tên/email chỉ điền khi đang trống). Gọi TRONG transaction đặt hàng.
     *
     * @return int customer id
     */
    public function resolveForCheckout(string $phone, string $fullName, ?string $email): int;

    /**
     * Khách đang đồng ý nhận tin qua kênh (`email`, `sms`, `zns`) × mục đích (`marketing`).
     */
    public function hasConsent(int $customerId, string $channel, string $purpose): bool;
}
