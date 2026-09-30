<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Nhãn trạng thái cho khách, TÍNH từ 4 chiều (order/payment/fulfillment/return) — docs/09-order/order.md §3.
 */
final class CustomerStatus
{
    /**
     * @return array{code: string, label: string}
     */
    public static function of(string $orderStatus, string $paymentStatus, string $fulfillmentStatus, string $returnStatus): array
    {
        $code = match (true) {
            $orderStatus === 'cancelled' => 'cancelled',
            ! in_array($returnStatus, ['none', ''], true) => in_array($returnStatus, ['returned', 'partially_returned'], true) ? 'returned' : 'returning',
            $orderStatus === 'completed' => 'completed',
            $fulfillmentStatus === 'delivered' => 'delivered',
            in_array($fulfillmentStatus, ['shipped', 'partially_shipped'], true) => 'shipping',
            $fulfillmentStatus === 'returned_to_sender' => 'delivery_failed',
            // Thu tiền khi giao bắt đầu ở `cod_pending`, không rơi vào nhánh chờ thanh toán.
            $orderStatus === 'pending' && in_array($paymentStatus, ['unpaid', 'failed'], true) => 'awaiting_payment',
            $orderStatus === 'pending' => 'awaiting_confirmation',
            default => 'preparing',
        };

        return ['code' => $code, 'label' => self::LABELS[$code]];
    }

    private const LABELS = [
        'cancelled' => 'Đã huỷ',
        'returning' => 'Đang đổi/trả',
        'returned' => 'Đã trả hàng',
        'completed' => 'Hoàn tất',
        'delivered' => 'Đã giao',
        'shipping' => 'Đang giao',
        'delivery_failed' => 'Giao không thành công',
        'awaiting_payment' => 'Chờ thanh toán',
        'awaiting_confirmation' => 'Chờ xác nhận',
        'preparing' => 'Đang chuẩn bị hàng',
    ];
}
