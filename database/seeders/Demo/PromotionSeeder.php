<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Promotion\Application\PromotionService;
use Modules\Promotion\Persistence\Models\Promotion;

/**
 * Mã giảm giá để thử checkout: CHAO10 (giảm 10%), GIAM50K (giảm 50.000 ₫).
 */
final class PromotionSeeder extends Seeder
{
    public function run(PromotionService $promotions): void
    {
        foreach ([
            ['CHAO10', 'Chào bạn mới — giảm 10%', 'percent_off', ['basis_points' => 1000]],
            ['GIAM50K', 'Giảm 50.000 ₫', 'amount_off', ['amount' => 50_000]],
        ] as [$code, $name, $action, $config]) {
            if (Promotion::query()->where('name', $name)->exists()) {
                continue;
            }
            $promotion = $promotions->save(['name' => $name, 'status' => 'active', 'starts_at' => null, 'ends_at' => null, 'priority' => 0,
                'stacking' => 'combinable', 'requires_voucher' => true, 'action_type' => $action, 'action_config' => $config, 'usage_limit' => null, 'budget_amount' => null]);
            $promotions->createVouchers($promotion, $code, null, 1, null, null);
        }
    }
}
