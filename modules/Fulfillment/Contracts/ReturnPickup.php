<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Modules\Fulfillment\Contracts\Data\CarrierShipment;
use Modules\Fulfillment\Contracts\Data\ReturnPickupData;

/**
 * Interface tuỳ chọn của `ShippingCarrier` (EP v3 X3): hãng đến lấy hàng trả tại nhà khách (đơn hoàn ngược). CSKH bấm
 * "Gọi hãng lấy hàng trả" trên yêu cầu đã duyệt; Core gọi ngoài transaction và chuyển yêu cầu sang "Đang gửi về" với mã
 * vận đơn hãng trả.
 */
interface ReturnPickup
{
    public function bookReturn(ReturnPickupData $data): CarrierShipment;
}
