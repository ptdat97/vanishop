<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;

/**
 * Ô "Việc cần làm" của giao hàng (0.3.101): vận đơn giao không thành công (gọi lại khách / hẹn giao lại); đặt vận đơn lỗi
 * (chỉ hiện khi có — hãng kết nối API). Mở trang vận đơn đã lọc (trang vẫn mở được khi menu "Giao hàng" ẩn, ADR-043).
 */
final class ShipmentDashboardTasks
{
    /**
     * @return list<array{key: string, label: string, count: int, url: string, tone: string, hint?: string, order: int}>
     */
    public function __invoke(): array
    {
        if (! Gate::allows('fulfillment.view')) {
            return [];
        }
        $count = fn (ShipmentStatus $status): int => Shipment::query()->where('status', $status)->count();
        $url = fn (ShipmentStatus $status): string => route('admin.fulfillment.shipments.index', ['status' => $status->value]);
        $tasks = [['key' => 'shipments.failed_attempt', 'label' => 'Giao không thành công', 'count' => $count(ShipmentStatus::FailedAttempt), 'url' => $url(ShipmentStatus::FailedAttempt), 'tone' => 'danger', 'hint' => 'Gọi lại khách, hẹn giao lại hoặc hoàn về', 'order' => 55]];
        $failedBooking = $count(ShipmentStatus::BookingFailed);
        if ($failedBooking > 0) {
            $tasks[] = ['key' => 'shipments.booking_failed', 'label' => 'Đặt vận đơn lỗi', 'count' => $failedBooking, 'url' => $url(ShipmentStatus::BookingFailed), 'tone' => 'danger', 'order' => 56];
        }

        return $tasks;
    }
}
