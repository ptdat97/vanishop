<?php

declare(strict_types=1);

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Domain event công khai (0.3.79, §3.5-2): khách xem trang sản phẩm (beacon sau khi trang tải, đã lọc trùng cùng phiên
 * trong 30 phút). `$customerId` null với khách vãng lai; không có lịch sử xem phía server.
 */
final readonly class ProductViewed
{
    use Dispatchable;

    public function __construct(
        public int $styleId,
        public ?int $customerId,
    ) {}
}
