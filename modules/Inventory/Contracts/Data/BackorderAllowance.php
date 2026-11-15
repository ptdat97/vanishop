<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

/**
 * Hạn mức đặt trước của một SKU (ADR-047): tối đa `limit` sản phẩm chờ hàng, dự kiến có hàng ngày `expectedOn` (Y-m-d).
 */
final readonly class BackorderAllowance
{
    public function __construct(
        public int $limit,
        public ?string $expectedOn = null,
    ) {}
}
