<?php

declare(strict_types=1);

namespace Modules\Cart\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Giỏ của khách (có `customerId`) còn hàng nhưng không hoạt động quá `vanishop.cart.abandoned_after_minutes`.
 * Phát một lần cho mỗi đợt không hoạt động; khách quay lại sửa giỏ rồi bỏ tiếp thì phát lại.
 * Plugin (vd. nhắc giỏ hàng) đọc chi tiết qua contract `Carts::forCustomer()` và tự kiểm tra consent trước khi gửi.
 */
final readonly class CartAbandoned implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $cartPublicId,
        public int $customerId,
        public int $itemCount,
        public int $subtotal,
        public string $currency,
        /** ISO-8601 */
        public string $lastActivityAt,
    ) {}
}
