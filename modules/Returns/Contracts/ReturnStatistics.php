<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts;

use DateTimeImmutable;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (đọc, 0.5.19): số liệu đổi/trả cho báo cáo (plugin `vani.reports`). Tính yêu cầu tạo trong [from, to),
 * trừ yêu cầu bị từ chối hoặc đã huỷ.
 */
#[PluginApi]
interface ReturnStatistics
{
    /**
     * Số lượng trả và tiền hoàn dự kiến theo SKU, số lượng giảm dần.
     *
     * @return list<array{variant_id: int, quantity: int, refund_amount: int}>
     */
    public function byVariant(DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Số yêu cầu và số lượng theo lý do (nhãn theo ngôn ngữ hiện tại), số yêu cầu giảm dần. `store_fault` (0.5.31) = lý
     * do "lỗi do cửa hàng"; `store_shipping_fee` = tổng phí ship trả hàng cửa hàng chịu của các yêu cầu đã hoàn tất.
     *
     * @return list<array{reason_code: string, label: string, store_fault: bool, requests: int, quantity: int, store_shipping_fee: int}>
     */
    public function byReason(DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Đổi/trả theo đơn gốc (0.5.95 — lợi nhuận sau trả hàng theo kỳ bán của đơn, reference-parity L43): mọi yêu cầu của các
     * đơn này, trừ yêu cầu bị từ chối hoặc đã huỷ, bất kể tạo lúc nào. `restocked` = dòng đã nhập lại kho. `store_shipping_fee`
     * = phí ship trả hàng cửa hàng chịu của yêu cầu đã hoàn tất.
     *
     * @param  list<int>  $orderIds
     * @return array<int, array{store_shipping_fee: int, lines: list<array{order_line_id: int, quantity: int, refund_amount: int, restocked: bool}>}> order id => số liệu (đơn không có yêu cầu thì không có mặt)
     */
    public function byOrders(array $orderIds): array;
}
