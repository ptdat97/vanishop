<?php

declare(strict_types=1);

namespace Modules\Cart\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Giỏ vừa thay đổi (thêm/sửa/xoá dòng, gộp). Dùng cho giỏ bỏ quên, cache mini-cart…
 */
final readonly class CartUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $cartId,
        public ?int $customerId,
    ) {}
}
