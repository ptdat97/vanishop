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
            'title' => 'Đổi/trả',
            'rows' => $returns->map(fn (ReturnRequest $return): array => [
                'label' => $return->number.($return->resolution === 'exchange' ? ' (đổi hàng)' : ''),
                'value' => ($labels[$return->status->value] ?? $return->status->value).' · '.$this->money->formatAmount($return->refund_amount, $order->currencyCode),
            ])->values()->all(),
            // Còn hàng trả được → tạo hộ khách; không thì mở yêu cầu gần nhất.
            'link' => $canCreate
                ? ['label' => 'Tạo yêu cầu đổi/trả', 'url' => route('admin.returns.returns.create', ['order' => $order->id])]
                : ['label' => 'Mở yêu cầu đổi/trả', 'url' => route('admin.returns.returns.show', ['return' => $returns->last()->id])],
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
                $actions[] = $transition(ReturnStatus::Approved, 'Duyệt đổi/trả', ['primary' => true, 'tone' => 'primary']);
            }
            if ($return->status->canMoveTo(ReturnStatus::Rejected)) {
                $actions[] = $transition(ReturnStatus::Rejected, 'Từ chối', ['tone' => 'danger', 'fields' => [['key' => 'note', 'label' => 'Lý do từ chối (gửi cho khách)', 'required' => true]]]);
            }
            if ($return->status->canMoveTo(ReturnStatus::InTransit) && $return->status !== ReturnStatus::Requested) {
                $actions[] = $transition(ReturnStatus::InTransit, 'Khách đã gửi hàng', ['confirm' => 'Ghi nhận khách đã gửi hàng về?']);
            }
            if ($return->status->canMoveTo(ReturnStatus::Received)) {
                $actions[] = ['key' => "return-{$return->id}-receive", 'label' => $prefix.'Đã nhận hàng (còn bán được)', 'url' => "{$url}/receive",
                    'primary' => true, 'tone' => 'primary', 'confirm' => 'Nhận hàng trả, nhập lại kho tất cả dòng? (Có hàng hỏng: khai ở trang yêu cầu.)'];
            }
        }
        if (Gate::allows('returns.refund') && $return->status->canMoveTo(ReturnStatus::Resolved)) {
            $actions[] = $return->resolution === 'exchange'
                ? ['key' => "return-{$return->id}-resolve", 'label' => $prefix.'Hoàn tất & tạo đơn đổi', 'url' => "{$url}/resolve", 'primary' => true, 'tone' => 'primary', 'confirm' => 'Hoàn tất đổi hàng và tạo đơn đổi?']
                : ['key' => "return-{$return->id}-resolve", 'label' => $prefix.'Hoàn tất & hoàn tiền', 'url' => "{$url}/resolve", 'primary' => true, 'tone' => 'primary', 'fields' => [
                    ['key' => 'amount', 'label' => 'Số tiền hoàn (tối đa '.$this->money->formatAmount($return->refund_amount, $currency).'; trừ phí hư hỏng nếu có)', 'required' => true, 'value' => (string) $return->refund_amount],
                    ['key' => 'note', 'label' => 'Ghi chú'],
                ]];
        }

        return $actions;
    }
}
