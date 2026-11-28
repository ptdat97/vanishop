<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Customer\Events\CustomerMerged;
use Modules\Promotion\Persistence\Models\Voucher;

/**
 * Gộp hồ sơ khách (L75): chuyển voucher riêng và cộng lượt đã dùng mỗi khách từ hồ sơ bị gộp sang hồ sơ được giữ.
 */
final class MergeCustomerPromotions
{
    public function handle(CustomerMerged $event): void
    {
        if ($event->sourceId === $event->targetId) {
            return;
        }

        DB::transaction(function () use ($event): void {
            Voucher::query()->where('customer_id', $event->sourceId)->update(['customer_id' => $event->targetId]);

            $now = now();
            $rows = DB::table('promotion_customer_usages')->where('customer_id', $event->sourceId)->get();
            foreach ($rows as $row) {
                DB::table('promotion_customer_usages')->insertOrIgnore([
                    'promotion_id' => $row->promotion_id,
                    'customer_id' => $event->targetId,
                    'used_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('promotion_customer_usages')
                    ->where('promotion_id', $row->promotion_id)
                    ->where('customer_id', $event->targetId)
                    ->increment('used_count', (int) $row->used_count, ['updated_at' => $now]);
            }
            DB::table('promotion_customer_usages')->where('customer_id', $event->sourceId)->delete();

            DB::table('promotion_usages')->where('customer_id', $event->sourceId)->update(['customer_id' => $event->targetId]);
        });
    }
}
