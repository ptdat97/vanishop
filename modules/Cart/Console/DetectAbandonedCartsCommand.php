<?php

declare(strict_types=1);

namespace Modules\Cart\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Domain\CartStatus;
use Modules\Cart\Events\CartAbandoned;
use Modules\Cart\Persistence\Models\Cart;

/**
 * Phát CartAbandoned cho giỏ của khách còn hàng, không hoạt động quá ngưỡng (chạy 5 phút/lần).
 * Giỏ vãng lai không có thông tin liên hệ → bỏ qua.
 */
final class DetectAbandonedCartsCommand extends Command
{
    protected $signature = 'vani:cart:detect-abandoned {--minutes= : Số phút không hoạt động (mặc định theo cấu hình)}';

    protected $description = 'Phát sự kiện giỏ hàng bị bỏ quên cho plugin (nhắc giỏ hàng)';

    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?? config('vanishop.cart.abandoned_after_minutes', 60));
        $count = 0;

        Cart::query()
            ->where('status', CartStatus::Active)
            ->whereNotNull('customer_id')
            ->where('last_activity_at', '<', now()->subMinutes($minutes))
            ->where(fn ($query) => $query->whereNull('abandoned_notified_at')->orWhereColumn('abandoned_notified_at', '<', 'last_activity_at'))
            ->whereHas('lines')
            ->chunkById(200, function ($carts) use (&$count): void {
                foreach ($carts as $cart) {
                    $count += $this->notify($cart) ? 1 : 0;
                }
            });

        $this->info("Đã phát {$count} sự kiện giỏ bị bỏ quên.");

        return self::SUCCESS;
    }

    private function notify(Cart $cart): bool
    {
        return DB::transaction(function () use ($cart): bool {
            // Cập nhật có điều kiện: hai tiến trình chạy chồng không phát trùng; giỏ vừa hoạt động lại thì bỏ.
            $claimed = Cart::query()->whereKey($cart->id)
                ->where('last_activity_at', $cart->last_activity_at)
                ->where(fn ($query) => $query->whereNull('abandoned_notified_at')->orWhereColumn('abandoned_notified_at', '<', 'last_activity_at'))
                ->update(['abandoned_notified_at' => now()]);
            if ($claimed === 0) {
                return false;
            }

            $lines = $cart->lines()->get(['quantity', 'unit_price_snapshot']);
            event(new CartAbandoned(
                $cart->public_id,
                (int) $cart->customer_id,
                (int) $lines->sum('quantity'),
                (int) $lines->sum(fn ($line): int => $line->quantity * $line->unit_price_snapshot),
                (string) $cart->currency_code,
                $cart->last_activity_at->toIso8601String(),
            ));

            return true;
        });
    }
}
