<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Listeners;

use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Contracts\Returns;

/**
 * Panel "Đổi/trả" trên trang đơn Admin (slot vani.admin.order.sidebar) — chỉ hiện khi đơn có yêu cầu.
 */
final class OrderReturnsPanel
{
    public function __construct(private readonly Returns $returns) {}

    /**
     * @return array{title: string, rows: list<array{label: string, value: string}>, link?: array{label: string, url: string}}|null
     */
    public function __invoke(OrderDetail $order): ?array
    {
        $returns = $this->returns->forOrder($order->id);
        if ($returns === []) {
            return null;
        }

        return [
            'title' => 'Đổi/trả',
            'rows' => array_map(fn ($return): array => ['label' => $return->number, 'value' => $return->status.' · '.number_format($return->refundAmount, 0, ',', '.').' ₫'], $returns),
            'link' => ['label' => 'Xử lý đổi/trả', 'url' => route('admin.returns.returns.show', ['return' => $returns[count($returns) - 1]->id])],
        ];
    }
}
