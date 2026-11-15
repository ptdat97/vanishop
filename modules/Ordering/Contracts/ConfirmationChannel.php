<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderData;

/**
 * Extension point (tag `vani.ordering.confirmation_channels`, EP v3 X2): kênh gửi yêu cầu xác nhận đơn cho khách (tin
 * Zalo OA, tổng đài gọi tự động…). Nhân viên bấm "Gửi xác nhận qua …" trên đơn "Cần xác nhận" (từng đơn hoặc hàng loạt);
 * Core gọi `request()` **ngoài** transaction, ghi lịch sử đơn. Khách trả lời qua webhook của plugin → plugin gọi
 * `OrderActions::confirm()` / `cancel()` với nguồn `system:<id plugin>`.
 */
interface ConfirmationChannel
{
    public const TAG = 'vani.ordering.confirmation_channels';

    public function code(): string;

    public function label(): string;

    /**
     * Gửi yêu cầu; trả thông báo cho nhân viên (vd. "Đã gửi tin xác nhận tới 0912…"). Không gửi được → ném
     * BusinessRuleViolation với lý do.
     */
    public function request(OrderData $order): string;
}
