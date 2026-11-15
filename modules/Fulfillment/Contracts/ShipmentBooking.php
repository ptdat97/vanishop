<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

/**
 * Chốt ② của ADR-046: "Đẩy lên hãng" — đặt vận đơn ở hãng có API (`CarrierCapabilities::$autoBooking`) cho vận đơn còn
 * chờ đặt hoặc đặt lỗi. Một lệnh dùng chung cho nút trên trang đơn, thao tác hàng loạt và plugin tự động hoá (vd. đặt theo
 * đợt giờ lấy hàng). Đặt ở hãng luôn chạy sau commit trong job idempotent theo vận đơn; hãng lỗi quá số lần thử →
 * `booking_failed` (hàng đợi "Sự cố giao hàng").
 */
interface ShipmentBooking
{
    /**
     * @param  list<int>  $shipmentIds
     * @return int số vận đơn đã đưa vào hàng đợi đặt (vận đơn đã có mã, đã huỷ hoặc của hãng nhập tay bị bỏ qua)
     */
    public function pushToCarrier(array $shipmentIds): int;
}
