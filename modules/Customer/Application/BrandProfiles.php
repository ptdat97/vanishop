<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Thống kê theo brand (số đơn, tổng chi tiêu, ngày mua đầu/cuối) — tính lại từ đơn hàng nên idempotent (R18).
 */
final class BrandProfiles
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly CurrentContext $context,
    ) {}

    public function recompute(int $customerId): void
    {
        $stats = $this->context->runAs(ContextScope::system('customer brand profiles'), fn (): array => $this->orders->customerBrandStats($customerId));

        DB::transaction(function () use ($customerId, $stats): void {
            DB::table('customer_brand_profiles')->where('customer_id', $customerId)->whereNotIn('brand_id', array_column($stats, 'brand_id'))->delete();
            foreach ($stats as $row) {
                DB::table('customer_brand_profiles')->upsert([[
                    'customer_id' => $customerId, 'brand_id' => $row['brand_id'], 'orders_count' => $row['orders_count'],
                    'total_spent' => $row['total_spent'], 'first_order_at' => $row['first_order_at'], 'last_order_at' => $row['last_order_at'],
                    'created_at' => now(), 'updated_at' => now(),
                ]], ['customer_id', 'brand_id'], ['orders_count', 'total_spent', 'first_order_at', 'last_order_at', 'updated_at']);
            }
        });
    }

    /**
     * @return list<array{brand_id: int, orders_count: int, total_spent: int, first_order_at: ?string, last_order_at: ?string}>
     */
    public function of(int $customerId): array
    {
        return DB::table('customer_brand_profiles')->where('customer_id', $customerId)->orderBy('brand_id')->get()
            ->map(fn (object $row): array => [
                'brand_id' => (int) $row->brand_id, 'orders_count' => (int) $row->orders_count, 'total_spent' => (int) $row->total_spent,
                'first_order_at' => $row->first_order_at, 'last_order_at' => $row->last_order_at,
            ])->all();
    }
}
