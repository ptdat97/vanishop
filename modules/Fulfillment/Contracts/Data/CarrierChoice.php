<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

/**
 * Kết quả của `CarrierAssignment`: hãng (+ dịch vụ) cho vận đơn mới và lý do (ghi vào lịch sử vận đơn).
 */
final readonly class CarrierChoice
{
    public function __construct(public string $carrierCode, public ?string $serviceCode = null, public ?string $reason = null) {}
}
