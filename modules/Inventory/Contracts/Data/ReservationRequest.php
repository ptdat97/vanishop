<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

/**
 * Yêu cầu giữ hàng cho một đơn/phiên checkout.
 */
final readonly class ReservationRequest
{
    /**
     * @param  list<ReservationLine>  $lines
     */
    public function __construct(
        public string $key,
        public array $lines,
        public ?int $ttlSeconds = null,
    ) {}
}
