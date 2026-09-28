<?php

declare(strict_types=1);

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Domain\ReservationStatus;
use Modules\Inventory\Persistence\Models\StockReservation;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Giải phóng reservation quá hạn (chạy mỗi phút). Idempotent.
 */
final class ReleaseExpiredReservationsCommand extends Command
{
    protected $signature = 'vani:inventory:release-expired {--limit=500}';

    protected $description = 'Giải phóng giữ hàng đã hết hạn';

    public function handle(InventoryReservation $reservations, CurrentContext $context): int
    {
        $keys = StockReservation::query()
            ->where('status', ReservationStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->distinct()
            ->limit((int) $this->option('limit'))
            ->pluck('reservation_key');

        $context->runAs(ContextScope::system('release expired reservations'), function () use ($keys, $reservations): void {
            foreach ($keys as $key) {
                $reservations->release((string) $key, 'expired');
            }
        });

        $this->info("Đã giải phóng {$keys->count()} reservation hết hạn.");

        return self::SUCCESS;
    }
}
