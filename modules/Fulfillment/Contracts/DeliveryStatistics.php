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
     * Kết quả vận đơn thu hộ (COD > 0) trong [from, to), dựa trên lúc giao xong hoặc lúc ghi nhận hoàn về lần đầu.
     * Tổng bằng số đã giao cộng số giao thất bại. Chỉ đếm vận đơn có mốc kết quả.
     *
     * @return array{cod_shipments: int, delivered: int, failed: int, failed_cod_amount: int}
     */
    public function cod(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
