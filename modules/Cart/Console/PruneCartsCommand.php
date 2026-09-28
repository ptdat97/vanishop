<?php

declare(strict_types=1);

namespace Modules\Cart\Console;

use Illuminate\Console\Command;
use Modules\Cart\Domain\CartStatus;
use Modules\Cart\Persistence\Models\Cart;

/**
 * Xoá giỏ không hoạt động quá hạn (mặc định 30 ngày). Giỏ đã đặt hàng được giữ lại để truy vết.
 */
final class PruneCartsCommand extends Command
{
    protected $signature = 'vani:cart:prune {--days= : Số ngày không hoạt động (mặc định theo cấu hình)}';

    protected $description = 'Xoá giỏ hàng bỏ quên quá hạn';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('vanishop.cart.ttl_days', 30));
        $deleted = 0;

        Cart::query()
            ->whereIn('status', [CartStatus::Active, CartStatus::Merged])
            ->where('last_activity_at', '<', now()->subDays($days))
            ->chunkById(500, function ($carts) use (&$deleted): void {
                $deleted += Cart::query()->whereKey($carts->modelKeys())->delete();
            });

        $this->info("Đã xoá {$deleted} giỏ không hoạt động hơn {$days} ngày.");

        return self::SUCCESS;
    }
}
