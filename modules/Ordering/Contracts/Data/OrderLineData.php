<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderLineData
{
    public function __construct(
        public int $id,
        public int $variantId,
        public string $sku,
        public string $productName,
        public int $quantity,
        /** Thành tiền dòng sau giảm giá phân bổ — cơ sở tính tiền hoàn khi trả hàng. */
        public int $totalAmount = 0,
        /** @var list<array{type: string, name: string, code: string, value: string}> tổ hợp tuỳ chọn của SKU lúc đặt (ADR-045) */
        public array $variantOptions = [],
        public ?string $brandName = null,
        /** @var array<string, array<string, scalar|null>> */
        public array $options = [],
        /** Đơn giá lúc đặt (snapshot) — đổi hàng cùng mẫu giữ đúng giá này (0.3.32). */
        public int $unitAmount = 0,
    ) {}
}
