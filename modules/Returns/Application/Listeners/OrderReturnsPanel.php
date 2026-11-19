<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Thẻ "Đổi/trả" trên trang đơn Admin (slot vani.admin.order.sidebar) — chỉ hiện khi đơn có yêu cầu hoặc còn hàng trả được.
 * 0.3.102: nhãn trạng thái tiếng Việt và thao tác ngay trên trang đơn theo từng yêu cầu — Duyệt / Từ chối → (Khách đã gửi
 * hàng) → Đã nhận hàng (còn bán được; hàng hỏng khai ở trang yêu cầu) → Hoàn tất & hoàn tiền (đổi hàng: tạo đơn đổi).
 */
final class OrderReturnsPanel
{
    public function __construct(
        private readonly ReturnService $service,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * @return array{title: string, rows: list<array{label: string, value: string}>, link?: array{label: string, url: string}, actions: list<array<string, mixed>>}|null
     */
    public function __invoke(OrderDetail $order): ?array
    {
        $returns = ReturnRequest::query()->where('order_id', $order->id)->orderBy('id')->get();
        $canCreate = Gate::allows('returns.manage') && array_sum($this->service->returnable($order->id)['lines']) > 0;
        if ($returns->isEmpty() && ! $canCreate) {
            return null;
        }
        $labels = (array) __('returns::labels.return_status');
        $several = $returns->count() > 1;

        $actions = [];
        foreach ($returns as $return) {
            array_push($actions, ...$this->actions($return, $several ? "{$return->number}: " : '', $order->currencyCode));
        }

        return [
            'title' => __('returns::texts.order_returns_panel.return_exchange'),
            'rows' => $returns->map(fn (ReturnRequest $return): array => [
                'label' => $return->number.($return->resolution === 'exchange' ? __('returns::texts.order_returns_panel.exchange') : ''),
                'value' => ($labels[$return->status->value] ?? $return->status->value).' · '.$this->money->formatAmount($return->refund_amount, $order->currencyCode),
            ])->values()->all(),
            // Còn hàng trả được → tạo hộ khách; không thì mở yêu cầu gần nhất.
            'link' => $canCreate
                ? ['label' => __('returns::texts.order_returns_panel.create_return_exchange_request'), 'url' => route('admin.returns.returns.create', ['order' => $order->id])]
                : ['label' => __('returns::texts.order_returns_panel.open_return_exchange_request'), 'url' => route('admin.returns.returns.show', ['return' => $returns->last()->id])],
            'actions' => $actions,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function actions(ReturnRequest $return, string $prefix, string $currency): array
    {
        $url = route('admin.returns.returns.show', ['return' => $return->id]);
        $actions = [];
        $transition = fn (ReturnStatus $to, string $label, array $extra = []): array => [
            'key' => "return-{$return->id}-{$to->value}", 'label' => $prefix.$label, 'url' => "{$url}/transition",
            'data' => ['to' => $to->value, 'lock_version' => $return->lock_version], ...$extra,
        ];

        if (Gate::allows('returns.manage')) {
            if ($return->status->canMoveTo(ReturnStatus::Approved)) {
                $actions[] = $transition(ReturnStatus::Approved, __('returns::texts.order_returns_panel.approve_return_exchange'), ['primary' => true, 'tone' => 'primary']);
            }
            if ($return->status->canMoveTo(ReturnStatus::Rejected)) {
                $actions[] = $transition(ReturnStatus::Rejected, __('returns::texts.order_returns_panel.reject'), ['tone' => 'danger', 'fields' => [['key' => 'note', 'label' => __('returns::texts.order_returns_panel.rejection_reason_sent_customer'), 'required' => true]]]);
            }
            if ($return->status->canMoveTo(ReturnStatus::InTransit) && $return->status !== ReturnStatus::Requested) {
                $actions[] = $transition(ReturnStatus::InTransit, __('returns::texts.order_returns_panel.customer_shipped_goods'), ['confirm' => __('returns::texts.order_returns_panel.record_customer_shipped_goods_back')]);
            }
            if ($return->status->canMoveTo(ReturnStatus::Received)) {
                $actions[] = ['key' => "return-{$return->id}-receive", 'label' => $prefix.__('returns::texts.order_returns_panel.received_still_sellable'), 'url' => "{$url}/receive",
                    'primary' => true, 'tone' => 'primary', 'confirm' => __('returns::texts.order_returns_panel.receive_return_restock_every_line')];
            }
        }
        if (Gate::allows('returns.refund') && $return->status->canMoveTo(ReturnStatus::Resolved)) {
            $actions[] = $return->resolution === 'exchange'
                ? ['key' => "return-{$return->id}-resolve", 'label' => $prefix.__('returns::texts.order_returns_panel.complete_create_exchange_order'), 'url' => "{$url}/resolve", 'primary' => true, 'tone' => 'primary', 'confirm' => __('returns::texts.order_returns_panel.complete_exchange_create_exchange_order')]
                : ['key' => "return-{$return->id}-resolve", 'label' => $prefix.__('returns::texts.order_returns_panel.complete_refund'), 'url' => "{$url}/resolve", 'primary' => true, 'tone' => 'primary', 'fields' => [
                    ['key' => 'amount', 'label' => __('returns::texts.order_returns_panel.refund_amount_most').$this->money->formatAmount($return->refund_amount, $currency).__('returns::texts.order_returns_panel.minus_damage_fees_if_any'), 'required' => true, 'value' => (string) $return->refund_amount],
                    ['key' => 'note', 'label' => __('returns::texts.order_returns_panel.note')],
                ]];
        }

        return $actions;
    }
}
