<?php

declare(strict_types=1);

namespace Modules\Ordering\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Ordering\Application\OrderQueries;

/**
 * Ô "Việc cần làm" của đơn hàng trên trang tổng quan (slot vani.admin.dashboard.tasks, 0.3.101): hàng đợi theo giai đoạn
 * vận hành + đơn để quá 24 giờ chưa xử lý xong. Bấm vào → danh sách đơn đã lọc.
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
        $url = fn (array $query): string => route('admin.orders.orders.index', $query);

        return [
            ['key' => 'orders.to_confirm', 'label' => 'Đơn cần xác nhận', 'count' => $counts['to_confirm'], 'url' => $url(['stage' => 'to_confirm']), 'tone' => 'warning', 'order' => 10],
            ['key' => 'orders.aged', 'label' => 'Đơn để quá '.self::AGED_HOURS.' giờ', 'count' => $this->queries->agedCount(self::AGED_HOURS), 'url' => $url(['aged' => self::AGED_HOURS]), 'tone' => 'danger', 'hint' => 'Chưa xác nhận hoặc chưa giao hãng', 'order' => 15],
            ['key' => 'orders.awaiting_payment', 'label' => 'Đơn chờ thanh toán', 'count' => $counts['awaiting_payment'], 'url' => $url(['stage' => 'awaiting_payment']), 'tone' => 'info', 'hint' => 'Chuyển khoản chưa về', 'order' => 20],
            ['key' => 'orders.to_ship', 'label' => 'Đơn chờ giao hãng', 'count' => $counts['to_ship'], 'url' => $url(['stage' => 'to_ship']), 'tone' => 'info', 'order' => 30],
            ['key' => 'orders.issue', 'label' => 'Đơn có sự cố giao hàng', 'hint' => 'Đặt vận đơn lỗi, giao không thành công, hoàn về', 'count' => $counts['issue'], 'url' => $url(['stage' => 'issue']), 'tone' => 'danger', 'order' => 50],
        ];
    }
}
