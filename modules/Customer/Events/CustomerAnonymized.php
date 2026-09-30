<?php

declare(strict_types=1);

namespace Modules\Customer\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Plugin giữ dữ liệu khách (loyalty, wishlist…) phải xoá/ẩn danh phần của mình khi nhận event này.
 */
final readonly class CustomerAnonymized implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $customerId) {}
}
