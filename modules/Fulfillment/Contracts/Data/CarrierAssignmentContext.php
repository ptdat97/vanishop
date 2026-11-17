<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Ngữ cảnh chọn hãng khi tạo vận đơn (EP v3 X3, `CarrierAssignment`): địa chỉ giao (snapshot đơn), số tiền thu hộ, tổng
 * đơn, số sản phẩm, phương thức giao khách chọn (`source` = mã hãng nếu phí do hãng báo).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
final readonly class CarrierAssignmentContext
{
    /**
     * @param  array<string, string>  $address  province_code, province_name, ward_code, ward_name, street_line
     * @param  array<string, mixed>  $shippingMethod  code, label, source, fee
     */
    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public array $address,
        public int $codAmount,
        public int $totalAmount,
        public int $itemCount,
        public array $shippingMethod,
    ) {}
}
