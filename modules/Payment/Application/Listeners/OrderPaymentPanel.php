<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Panel "Thanh toán" trên trang đơn Admin (slot vani.admin.order.sidebar).
 */
final class OrderPaymentPanel
{
    public function __construct(private readonly GatewayRegistry $gateways, private readonly MoneyFormatter $money) {}

    /**
     * @return array{title: string, rows: list<array{label: string, value: string}>, link?: array{label: string, url: string}}
     */
    public function __invoke(OrderDetail $order): array
    {
        $money = fn (int $amount): string => $this->money->formatAmount($amount, $order->currencyCode);
        $rows = [];
        foreach (Payment::query()->where('order_id', $order->id)->orderBy('id')->get() as $payment) {
            $rows[] = ['label' => $this->gateways->get($payment->gateway_code)?->label() ?? $payment->gateway_code, 'value' => "{$payment->status->value} · {$money($payment->amount)}"];
            if ($payment->refunded_amount > 0) {
                $pending = Refund::query()->where('payment_id', $payment->id)->where('status', 'requested')->sum('amount');
                $rows[] = ['label' => 'Đã hoàn', 'value' => $money($payment->refunded_amount).($pending > 0 ? ' (chờ chuyển trả '.$money((int) $pending).')' : '')];
            }
        }

        return [
            'title' => 'Thanh toán',
            'rows' => $rows === [] ? [['label' => 'Chưa có thanh toán', 'value' => '']] : $rows,
            'link' => ['label' => 'Quản lý thanh toán', 'url' => route('admin.payment.payments.index')],
        ];
    }
}
