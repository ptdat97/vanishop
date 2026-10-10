<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Contracts\Returns;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Panel "Đổi/trả" trên trang đơn Admin (slot vani.admin.order.sidebar) — chỉ hiện khi đơn có yêu cầu.
 */
final class OrderReturnsPanel
{
    public function __construct(
        private readonly Returns $returns,
        private readonly ReturnService $service,
    ) {}

    /**
     * @return array{title: string, rows: list<array{label: string, value: string}>, link?: array{label: string, url: string}}|null
     */
    public function __invoke(OrderDetail $order): ?array
    {
        $returns = $this->returns->forOrder($order->id);
        $canCreate = Gate::allows('returns.manage') && array_sum($this->service->returnable($order->id)['lines']) > 0;
        if ($returns === [] && ! $canCreate) {
            return null;
        }

        return [
            'title' => 'Đổi/trả',
            'rows' => array_map(fn ($return): array => ['label' => $return->number, 'value' => $return->status.' · '.app(MoneyFormatter::class)->formatAmount($return->refundAmount, $order->currencyCode)], $returns),
            // Còn hàng trả được → tạo hộ khách; không thì mở yêu cầu gần nhất.
            'link' => $canCreate
                ? ['label' => 'Tạo yêu cầu đổi/trả', 'url' => route('admin.returns.returns.create', ['order' => $order->id])]
                : ['label' => 'Xử lý đổi/trả', 'url' => route('admin.returns.returns.show', ['return' => $returns[count($returns) - 1]->id])],
        ];
    }
}
