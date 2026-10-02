<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Dữ liệu gửi hãng để đặt vận đơn. `publicId` dùng làm mã đơn phía hãng (hãng tự khử trùng khi đặt lại).
 */
final readonly class ShipmentData
{
    /**
     * @param  list<array{sku: string, name: string, quantity: int}>  $items
     * @param  array{full_name: string, phone: string}  $recipient
     * @param  array<string, string>  $address
     */
    public function __construct(
        public string $publicId,
        public string $orderNumber,
        public int $locationId,
        public ?string $serviceCode,
        public array $items,
        public array $recipient,
        public array $address,
        public Money $codAmount,
        public ?string $trackingNumber = null,
    ) {}
}
