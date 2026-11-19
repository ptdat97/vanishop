<?php

declare(strict_types=1);

namespace Modules\Ordering\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Ordering\Application\OrderQueries;

/**
 * Ô "Việc cần làm" của đơn hàng trên trang tổng quan (slot vani.admin.dashboard.tasks, 0.3.101): hàng đợi theo giai đoạn
 * vận hành + đơn để quá 24 giờ chưa xử lý xong + đơn hoàn tất còn COD chưa thu (0.4.52). Bấm vào → danh sách đơn đã lọc.
 */
final class OrderDashboardTasks
{
    public const AGED_HOURS = 24;

    public function __construct(private readonly OrderQueries $queries) {}

    /**
     * @return list<array{key: string, label: string, count: int, url: string, tone: string, hint?: string, order: int}>
     */
    public function __invoke(): array
    {
        if (! Gate::allows('orders.view')) {
            return [];
        }
        $counts = $this->queries->stageCounts([]);
        $codPending = $this->queries->completedAwaitingCodCount();
        $url = fn (array $query): string => route('admin.orders.orders.index', $query);

        // Chỉ mục cuối có thể null → lọc xong vẫn là list.
        return array_filter([
            ['key' => 'orders.to_confirm', 'label' => __('ordering::texts.order_dashboard_tasks.orders_confirm'), 'count' => $counts['to_confirm'], 'url' => $url(['stage' => 'to_confirm']), 'tone' => 'warning', 'order' => 10],
            ['key' => 'orders.aged', 'label' => __('ordering::texts.order_dashboard_tasks.orders_older_than').self::AGED_HOURS.__('ordering::texts.order_dashboard_tasks.hours'), 'count' => $this->queries->agedCount(self::AGED_HOURS), 'url' => $url(['aged' => self::AGED_HOURS]), 'tone' => 'danger', 'hint' => __('ordering::texts.order_dashboard_tasks.not_yet_confirmed_not_yet'), 'order' => 15],
            ['key' => 'orders.awaiting_payment', 'label' => __('ordering::texts.order_dashboard_tasks.orders_awaiting_payment'), 'count' => $counts['awaiting_payment'], 'url' => $url(['stage' => 'awaiting_payment']), 'tone' => 'info', 'hint' => __('ordering::texts.order_dashboard_tasks.bank_transfer_not_received_yet'), 'order' => 20],
            ['key' => 'orders.to_ship', 'label' => __('ordering::texts.order_dashboard_tasks.orders_pack'), 'hint' => __('ordering::texts.order_dashboard_tasks.print_slips_pack_hand_carrier'), 'count' => $counts['to_ship'], 'url' => $url(['stage' => 'to_ship']), 'tone' => 'info', 'order' => 30],
            ['key' => 'orders.issue', 'label' => __('ordering::texts.order_dashboard_tasks.orders_delivery_issues'), 'hint' => __('ordering::texts.order_dashboard_tasks.shipment_booking_failed_delivery_failed'), 'count' => $counts['issue'], 'url' => $url(['stage' => 'issue']), 'tone' => 'danger', 'order' => 50],
            // Chỉ hiện khi có: thường do hoàn tất ngoài trình tự mà chưa bấm "Đã thu tiền".
            $codPending > 0 ? ['key' => 'orders.completed_cod_pending', 'label' => __('ordering::texts.order_dashboard_tasks.completed_orders_cod_not_collected'), 'hint' => __('ordering::texts.order_dashboard_tasks.open_order_payment_tab_cash'), 'count' => $codPending, 'url' => $url(['status' => 'completed', 'payment_status' => 'cod_pending']), 'tone' => 'warning', 'order' => 55] : null,
        ]);
    }
}
