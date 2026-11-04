<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Listeners;

use Modules\Customer\Persistence\Models\Customer;
use Modules\Ordering\Events\OrderPlaced;

/**
 * Khách đặt hàng bằng ngôn ngữ nào thì đó là ngôn ngữ khách dùng (ADR-038 §8) — cho thông báo không gắn đơn.
 */
final class RememberPreferredLocale
{
    public function handle(OrderPlaced $event): void
    {
        if ($event->customerId !== null && $event->locale !== null) {
            Customer::query()->whereKey($event->customerId)->update(['preferred_locale' => $event->locale]);
        }
    }
}
