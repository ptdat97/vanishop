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

    /**
     * COD đã giao trong [from, to), hiện chưa khớp bảng kê. Tuổi tính theo số ngày đủ 24 giờ kể từ lúc giao.
     *
     * @return list<array{carrier: string, bucket: string, shipments: int, amount: int}>
     */
    public function codAging(DateTimeImmutable $from, DateTimeImmutable $to, DateTimeImmutable $now): array;

    /**
     * Phí hãng thực tế theo đơn (0.5.95 — lợi nhuận sau phí giao, reference-parity L43): tổng `actual_carrier_fee` của các vận
     * đơn đã ghi phí; `missing` = số vận đơn đã gửi hãng mà chưa có phí (chưa khớp bảng kê).
     *
     * @param  list<int>  $orderIds
     * @return array<int, array{fee: int, missing: int}> order id => phí (đơn không có vận đơn thì không có mặt)
     */
    public function carrierFees(array $orderIds): array;
}
