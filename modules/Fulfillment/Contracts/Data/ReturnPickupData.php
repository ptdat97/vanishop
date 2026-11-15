<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

/**
 * Dữ liệu gọi hãng đến lấy hàng trả tại nhà khách (EP v3 X3, `ReturnPickup`): lấy tại địa chỉ giao của đơn, trả về kho
 * đã giao hàng.
 *
 * @param  array<string, string>  $pickupAddress
 * @param  array{full_name: string, phone: string}  $contact
 */
final readonly class ReturnPickupData
{
    /**
     * @param  array<string, string>  $pickupAddress
     * @param  array{full_name: string, phone: string}  $contact
     */
    public function __construct(
        public string $returnNumber,
        public string $orderNumber,
        public int $locationId,
        public array $pickupAddress,
        public array $contact,
        public int $itemCount,
    ) {}
}
