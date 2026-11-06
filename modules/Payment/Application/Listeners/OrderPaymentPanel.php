<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreClock;

/**
 * Thẻ "Thanh toán" trên trang đơn Admin (slot vani.admin.order.sidebar) — 0.3.96: nhãn trạng thái, thao tác ngay trên trang
 * đơn: xác nhận đã nhận chuyển khoản (bước tiếp theo khi đơn chờ thanh toán), hoàn tiền, đánh dấu đã chuyển trả. Giao dịch
 * gộp vào lịch sử đơn (slot vani.admin.order.timeline).
 */
final class OrderPaymentPanel
{
    public function __construct(private readonly GatewayRegistry $gateways, private readonly MoneyFormatter $money) {}

    /**
     * @return array{title: string, rows: list<array{label: string, value: string}>, link: array{label: string, url: string}, actions: list<array<string, mixed>>}
     */
    public function __invoke(OrderDetail $order): array
    {
        $money = fn (int $amount): string => $this->money->formatAmount($amount, $order->currencyCode);
        $labels = (array) __('payment::labels.payment_status');
        $rows = [];
        $actions = [];
        $base = route('admin.payment.payments.index');
        foreach (Payment::query()->where('order_id', $order->id)->orderBy('id')->get() as $payment) {
            $gateway = $this->gateways->get($payment->gateway_code);
            $rows[] = ['label' => $gateway?->label() ?? $payment->gateway_code, 'value' => ($labels[$payment->status->value] ?? $payment->status->value).' · '.$money($payment->amount)];
            $pendingRefunds = Refund::query()->where('payment_id', $payment->id)->where('status', 'requested')->get();
            if ($payment->refunded_amount > 0) {
                $rows[] = ['label' => 'Đã hoàn', 'value' => $money($payment->refunded_amount).($pendingRefunds->isNotEmpty() ? ' (chờ chuyển trả '.$money((int) $pendingRefunds->sum('amount')).')' : '')];
            }

            if (Gate::allows('payments.confirm') && ($gateway?->capabilities()->manualConfirmation ?? false)
                && in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired], true)) {
                $actions[] = ['key' => "{$payment->id}-confirm", 'label' => 'Xác nhận đã nhận tiền', 'url' => "{$base}/{$payment->id}/confirm", 'primary' => true, 'tone' => 'primary',
                    'fields' => [['key' => 'note', 'label' => 'Ghi chú (mã giao dịch ngân hàng, người kiểm tra…)', 'required' => true]]];
            }
            if (Gate::allows('payments.refund')) {
                if ($payment->status->hasCollected() && $payment->refunded_amount < $payment->amount) {
                    $actions[] = ['key' => "{$payment->id}-refund", 'label' => 'Hoàn tiền', 'url' => "{$base}/{$payment->id}/refunds", 'tone' => 'secondary',
                        'data' => ['idempotency_key' => (string) Str::ulid()],
                        'fields' => [
                            ['key' => 'amount', 'label' => 'Số tiền hoàn (tối đa '.$money($payment->amount - $payment->refunded_amount).')', 'required' => true, 'value' => (string) ($payment->amount - $payment->refunded_amount)],
                            ['key' => 'reason', 'label' => 'Lý do', 'required' => true],
                        ]];
                }
                foreach ($pendingRefunds as $refund) {
                    $actions[] = ['key' => "refund-{$refund->id}-complete", 'label' => 'Đã chuyển trả '.$money($refund->amount), 'url' => route('admin.payment.refunds.complete', $refund->id), 'tone' => 'secondary',
                        'fields' => [['key' => 'note', 'label' => 'Ghi chú (mã giao dịch chuyển trả)', 'required' => true]]];
                }
            }
        }

        return [
            'title' => 'Thanh toán',
            'rows' => $rows === [] ? [['label' => 'Chưa có thanh toán', 'value' => '']] : $rows,
            'link' => ['label' => 'Quản lý thanh toán', 'url' => $base],
            'actions' => $actions,
        ];
    }

    /**
     * Giao dịch xác nhận/thu/hoàn của đơn cho lịch sử trang đơn (bỏ bước khởi tạo/truy vấn kỹ thuật).
     *
     * @return list<array{ts: int, at: string, title: string, detail: string|null, source: string}>
     */
    public function timeline(OrderDetail $order): array
    {
        $types = ['callback' => 'Cổng báo kết quả', 'confirm' => 'Xác nhận thanh toán', 'capture' => 'Thu tiền', 'refund' => 'Hoàn tiền', 'cod_collected' => 'Đã thu COD'];

        return DB::table('payment_transactions')->join('payments', 'payments.id', '=', 'payment_transactions.payment_id')
            ->where('payments.order_id', $order->id)
            ->whereIn('payment_transactions.type', array_keys($types))
            ->orderBy('payment_transactions.id')
            ->get(['payment_transactions.type', 'payment_transactions.amount', 'payment_transactions.status', 'payment_transactions.created_at'])
            ->map(fn (object $row): array => [
                'ts' => strtotime((string) $row->created_at.' UTC') ?: 0,
                'at' => (string) StoreClock::format((string) $row->created_at),
                'title' => 'Thanh toán: '.$types[$row->type],
                'detail' => trim(($row->amount !== null ? $this->money->formatAmount((int) $row->amount, $order->currencyCode) : '').' '.$row->status) ?: null,
                'source' => 'payment',
            ])->all();
    }
}
