<?php

declare(strict_types=1);

namespace Plugin\Preorder\Infrastructure;

use Modules\Inventory\Contracts\BackorderPolicy;
use Modules\Inventory\Contracts\Data\BackorderAllowance;
use Modules\Inventory\Contracts\IncomingStock;
use Plugin\Preorder\PreorderServiceProvider;

/**
 * Hạn mức đặt trước = phần trăm (cấu hình) của số hàng đang về, làm tròn xuống; ngày dự kiến theo hàng đang về.
 */
final class IncomingBackorderPolicy implements BackorderPolicy
{
    public function __construct(private readonly IncomingStock $incoming) {}

    public function allowances(array $variantIds): array
    {
        $percent = max(0, min(100, (int) vani_setting(PreorderServiceProvider::ID, 'share_percent', 100)));
        $result = [];
        foreach ($this->incoming->forVariants($variantIds) as $variantId => $incoming) {
            $limit = intdiv($incoming->quantity * $percent, 100);
            if ($limit > 0) {
                $result[$variantId] = new BackorderAllowance($limit, $incoming->expectedOn);
            }
        }

        return $result;
    }
}
