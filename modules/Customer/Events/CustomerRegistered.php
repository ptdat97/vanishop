<?php

declare(strict_types=1);

namespace Modules\Customer\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Khách xác thực SĐT lần đầu (profile ẩn → tài khoản). $claimedGuestProfile = đã có lịch sử đơn vãng lai.
 */
final readonly class CustomerRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $customerId, public string $publicId, public bool $claimedGuestProfile) {}
}
