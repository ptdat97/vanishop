<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Contracts\Data\RiskSignal;
use Modules\Ordering\Contracts\OrderRisks;
use Modules\Ordering\Contracts\OrderRiskSignal;

/**
 * Gộp tín hiệu rủi ro của mọi `OrderRiskSignal` đang bật (EP v3 X2). Lỗi của một plugin bị bỏ (log qua Extensions::call),
 * kết quả sai kiểu bị bỏ; nguồn tín hiệu là id plugin. Thứ tự: mức cao trước.
 */
final class OrderRiskService implements OrderRisks
{
    private const RANK = ['high' => 0, 'medium' => 1, 'low' => 2];

    public function __construct(private readonly Extensions $extensions) {}

    public function forOrders(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
        if ($orderIds === []) {
            return [];
        }
        $result = [];
        foreach ($this->extensions->implementations(OrderRiskSignal::TAG, OrderRiskSignal::class, fn (OrderRiskSignal $signal): string => $signal::class) as $assessor) {
            $source = $this->extensions->ownerOf($assessor) ?? 'core';
            $found = $this->extensions->call($assessor, fn (): array => $assessor->assess($orderIds), [], 'risk.assess');
            foreach ((array) $found as $orderId => $signals) {
                if (! in_array((int) $orderId, $orderIds, true) || ! is_array($signals)) {
                    continue;
                }
                foreach ($signals as $signal) {
                    if ($signal instanceof RiskSignal) {
                        $result[(int) $orderId][] = $signal->withSource($source);
                    }
                }
            }
        }
        foreach ($result as &$signals) {
            usort($signals, fn (RiskSignal $a, RiskSignal $b): int => self::RANK[$a->level] <=> self::RANK[$b->level]);
        }

        return $result;
    }

    public function hasHigh(int $orderId): bool
    {
        foreach ($this->forOrders([$orderId])[$orderId] ?? [] as $signal) {
            if ($signal->level === 'high') {
                return true;
            }
        }

        return false;
    }
}
