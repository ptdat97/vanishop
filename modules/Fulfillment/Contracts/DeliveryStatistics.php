<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use DateTimeImmutable;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (đọc, 0.5.19): số liệu giao hàng cho báo cáo (plugin `vani.reports`).
 */
#[PluginApi]
interface DeliveryStatistics
{
    /**
     * Vận đơn thu hộ (COD > 0) tạo trong [from, to): tổng số, số đã giao, số giao thất bại (đang hoàn / đã hoàn về) và tiền
     * COD của các vận đơn thất bại.
     *
     * @return array{cod_shipments: int, delivered: int, failed: int, failed_cod_amount: int}
     */
    public function cod(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
