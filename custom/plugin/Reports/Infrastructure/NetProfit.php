<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure;

use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Fulfillment\Contracts\DeliveryStatistics;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Returns\Contracts\ReturnStatistics;

/**
 * Lợi nhuận sau trả hàng và phí giao theo kỳ bán của đơn gốc (reference-parity L43): đơn đặt trong kỳ — hàng trả, tiền hoàn,
 * phí hãng của các đơn đó tính về ngày đặt, bất kể xảy ra lúc nào. Chỉ dòng có giá vốn lúc bán vào lợi nhuận (như lợi nhuận
 * gộp); tiền hoàn của dòng thiếu giá vốn báo riêng. Hàng trả đã nhập lại kho trả lại giá vốn; hàng trả không nhập kho mất giá
 * vốn.
 */
final class NetProfit
{
    private const BATCH = 1000;

    public function __construct(
        private readonly OrderStatistics $orders,
        private readonly ReturnStatistics $returns,
        private readonly DeliveryStatistics $deliveries,
        private readonly GrossProfit $grossProfit,
    ) {}

    /**
     * @return array<string, array{revenue: int, cost: int, quantity: int, uncosted_revenue: int, refunds: int, uncosted_refunds: int, returned_cost: int, returned_quantity: int, carrier_fees: int, missing_fees: int, return_shipping: int}> ngày => số liệu
     */
    public function byDay(ReportPeriod $period): array
    {
        $days = [];
        $blank = ['revenue' => 0, 'cost' => 0, 'quantity' => 0, 'uncosted_revenue' => 0, 'refunds' => 0, 'uncosted_refunds' => 0, 'returned_cost' => 0, 'returned_quantity' => 0, 'carrier_fees' => 0, 'missing_fees' => 0, 'return_shipping' => 0];
        foreach ($this->grossProfit->lines($period) as $line) {
            $days[$line['day']] ??= $blank;
            $days[$line['day']]['revenue'] += $line['costed_revenue'];
            $days[$line['day']]['uncosted_revenue'] += $line['revenue'] - $line['costed_revenue'];
            $days[$line['day']]['cost'] += $line['cost_at_sale'];
            $days[$line['day']]['quantity'] += $line['quantity'];
        }

        $afterId = 0;
        while (($batch = $this->orders->placedOrderDays($period->from, $period->to, $period->timezone, $afterId, self::BATCH)) !== []) {
            $afterId = (int) array_key_last($batch);
            $returns = $this->returns->byOrders(array_keys($batch));
            $costs = $this->orders->unitCostsAtSale(array_merge([], ...array_map(fn (array $order): array => array_column($order['lines'], 'order_line_id'), array_values($returns))));
            foreach ($returns as $orderId => $order) {
                $day = $batch[$orderId] ?? null;
                if ($day === null) {
                    continue;
                }
                $days[$day] ??= $blank;
                $days[$day]['return_shipping'] += $order['store_shipping_fee'];
                foreach ($order['lines'] as $line) {
                    $days[$day]['returned_quantity'] += $line['quantity'];
                    $unitCost = $costs[$line['order_line_id']] ?? null;
                    if ($unitCost === null) {
                        $days[$day]['uncosted_refunds'] += $line['refund_amount'];

                        continue;
                    }
                    $days[$day]['refunds'] += $line['refund_amount'];
                    $days[$day]['returned_cost'] += $line['restocked'] ? $unitCost * $line['quantity'] : 0;
                }
            }
            foreach ($this->deliveries->carrierFees(array_keys($batch)) as $orderId => $fee) {
                $day = $batch[$orderId];
                $days[$day] ??= $blank;
                $days[$day]['carrier_fees'] += $fee['fee'];
                $days[$day]['missing_fees'] += $fee['missing'];
            }
        }
        ksort($days);

        return $days;
    }

    /**
     * @param  array{revenue: int, cost: int, quantity: int, refunds: int, returned_cost: int, returned_quantity: int, carrier_fees: int, return_shipping: int}  $day
     * @return array{profit: int, return_rate: float|null}
     */
    public static function result(array $day): array
    {
        return [
            'profit' => $day['revenue'] - $day['refunds'] - ($day['cost'] - $day['returned_cost']) - $day['carrier_fees'] - $day['return_shipping'],
            'return_rate' => $day['quantity'] === 0 ? null : round($day['returned_quantity'] * 100 / $day['quantity'], 1),
        ];
    }
}
