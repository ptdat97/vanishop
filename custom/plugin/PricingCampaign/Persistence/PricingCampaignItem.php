<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $pricing_campaign_id
 * @property int $product_id
 * @property string $strategy fixed_price | percent | amount
 * @property int $value
 * @property int|null $quota
 * @property int|null $per_customer
 * @property int $position
 */
final class PricingCampaignItem extends Model
{
    public const STRATEGIES = ['fixed_price', 'percent', 'amount'];

    public $timestamps = false;

    protected $table = 'plg_pricing_campaign_items';

    protected $fillable = ['pricing_campaign_id', 'product_id', 'strategy', 'value', 'quota', 'per_customer', 'position'];

    protected function casts(): array
    {
        return ['pricing_campaign_id' => 'integer', 'product_id' => 'integer', 'value' => 'integer', 'quota' => 'integer', 'per_customer' => 'integer', 'position' => 'integer'];
    }
}
