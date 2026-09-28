<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts\Data;

use DateTimeImmutable;

/**
 * Dữ liệu để ReturnPolicy quyết định: đơn, dòng muốn trả, thời điểm giao gần nhất.
 */
final readonly class ReturnContext
{
    /**
     * @param  array<int, int>  $lines  order_line_id => số lượng muốn trả
     */
    public function __construct(
        public int $orderId,
        public int $brandId,
        public array $lines,
        public string $reasonCode,
        public ?DateTimeImmutable $deliveredAt,
        public DateTimeImmutable $now,
        public string $source,
    ) {}
}
