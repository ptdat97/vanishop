<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Modules\Customer\Persistence\Models\Customer;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Thống kê mua của khách (số đơn, tổng chi tiêu, ngày mua đầu/cuối) — tính lại từ đơn hàng nên idempotent (R18).
 * Mua theo brand: báo cáo từ snapshot brand trên dòng đơn, không lưu ở đây.
 */
final class CustomerStats
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly CurrentContext $context,
    ) {}

    public function recompute(int $customerId): void
    {
        $stats = $this->context->runAs(ContextScope::system('customer stats'), fn (): array => $this->orders->customerStats($customerId));

        Customer::query()->whereKey($customerId)->update($stats);
    }

    /**
     * @return array{orders_count: int, total_spent: int, first_order_at: ?string, last_order_at: ?string}
     */
    public function of(Customer $customer): array
    {
        return [
            'orders_count' => (int) $customer->orders_count,
            'total_spent' => (int) $customer->total_spent,
            'first_order_at' => $customer->first_order_at?->toIso8601String(),
            'last_order_at' => $customer->last_order_at?->toIso8601String(),
        ];
    }
}
