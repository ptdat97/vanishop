<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (EP v3 X2): lệnh của nhân viên trên đơn cho plugin tự động hoá gọi — **cùng** chính sách, hook chặn
 * (`vani.order.before_confirm`), lịch sử `order_events` và audit như khi nhân viên bấm (ADR-046: không có đường tắt cho
 * máy). `source`: `staff` hoặc `system:<id plugin>` (vd. `system:vani.cod-risk-guard`), ghi vào lịch sử đơn.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface OrderActions
{
    /**
     * Xác nhận đơn đang chờ. Hook chặn trả lý do → OrderActionRejected (`order.confirm_blocked`).
     *
     * @throws OrderActionRejected
     */
    public function confirm(int $orderId, string $reason, string $source): void;

    /**
     * Huỷ đơn theo chính sách huỷ của nhân viên (`OrderPolicy::staffCanCancel`).
     *
     * @throws OrderActionRejected
     */
    public function cancel(int $orderId, string $reason, string $source, ?string $reasonCode = null): void;

    /**
     * Ghi chú nội bộ vào lịch sử đơn (vd. "Khách bấm Đồng ý qua Zalo OA lúc 10:02").
     */
    public function addNote(int $orderId, string $note, string $source): void;
}
