<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Plugin\PricingCampaign\Application\PricingCampaigns;
use Plugin\PricingCampaign\Persistence\PricingCampaign;

final class PricingCampaignSeeder extends Seeder
{
    public function run(PricingCampaigns $campaigns): void
    {
        $products = DemoCatalog::productIds();
        if ($products === []) {
            return;
        }

        $group = CustomerGroup::query()->firstOrCreate(['code' => 'demo-vip'], ['name' => 'VIP demo']);
        $now = now()->toImmutable()->utc();
        $defaults = [
            'status' => 'active',
            'starts_at' => $now->subHour(),
            'ends_at' => $now->addDays(30),
            'show_countdown' => true,
            'show_stock' => false,
            'badge_style' => 'dark',
        ];

        $flashProduct = $products[0];
        $hotDealProduct = $products[1] ?? $flashProduct;
        $weekendProduct = $products[2] ?? $hotDealProduct;
        $memberProduct = $products[3] ?? $flashProduct;

        foreach ([
            [
                'template' => 'flash_sale', 'name' => '[demo] Flash sale', 'label' => 'Flash sale Vani Studio',
                'badge' => 'Flash sale', 'badge_style' => 'danger', 'content' => 'Ưu đãi giới hạn **10 suất**.',
                'priority' => 100, 'ends_at' => $now->addDays(7), 'show_stock' => true,
                'quota_total' => 10, 'per_customer_total' => 2,
                'items' => [['product_id' => $flashProduct, 'strategy' => 'percent', 'value' => 25, 'quota' => 10]],
            ],
            [
                'template' => 'hot_deal', 'name' => '[demo] Hot deal', 'label' => 'Hot deal sắp mở',
                'badge' => 'Hot deal', 'badge_style' => 'warning', 'priority' => 90, 'starts_at' => $now->addDay(),
                'items' => [['product_id' => $hotDealProduct, 'strategy' => 'amount', 'value' => 50_000]],
            ],
            [
                'template' => 'weekend', 'name' => '[demo] Sale cuối tuần', 'label' => 'Sale cuối tuần',
                'badge' => 'Cuối tuần', 'badge_style' => 'success', 'priority' => 80,
                'recurrence' => [['days' => [6, 7], 'from' => '10:00', 'to' => '22:00']],
                'items' => [['product_id' => $weekendProduct, 'strategy' => 'percent', 'value' => 20]],
            ],
            [
                'template' => 'member', 'name' => '[demo] Giá thành viên', 'label' => 'Giá thành viên VIP',
                'badge' => 'VIP', 'priority' => 110, 'customer_group_id' => $group->id,
                'items' => [['product_id' => $memberProduct, 'strategy' => 'percent', 'value' => 30]],
            ],
        ] as $campaign) {
            if (! PricingCampaign::query()->where('name', $campaign['name'])->exists()) {
                $campaigns->save(array_replace($defaults, $campaign));
            }
        }
    }
}
