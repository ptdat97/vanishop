<?php

declare(strict_types=1);

namespace Modules\Promotion\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Promotion\Domain\Stacking;

/**
 * @property int $id
 * @property string $name
 * @property int|null $campaign_id campaign sở hữu lịch chạy (0.3.36)
 * @property string $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $priority
 * @property Stacking $stacking
 * @property bool $requires_voucher
 * @property string $action_type
 * @property array<string, mixed> $action_config
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property int|null $budget_amount
 * @property int $budget_used_amount
 * @property int $lock_version
 */
final class Promotion extends Model
{
    protected $fillable = ['campaign_id', 'name', 'status', 'starts_at', 'ends_at', 'priority', 'stacking', 'requires_voucher', 'action_type', 'action_config',
        'usage_limit', 'usage_count', 'budget_amount', 'budget_used_amount', 'lock_version'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'priority' => 'integer', 'stacking' => Stacking::class,
            'requires_voucher' => 'boolean', 'action_config' => 'array', 'usage_limit' => 'integer', 'usage_count' => 'integer',
            'budget_amount' => 'integer', 'budget_used_amount' => 'integer', 'lock_version' => 'integer',
        ];
    }

    /**
     * @return HasMany<PromotionRuleRecord, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(PromotionRuleRecord::class);
    }

    /**
     * @return HasMany<Voucher, $this>
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function isRunningAt(int $now): bool
    {
        return $this->status === 'active'
            && ($this->starts_at === null || $this->starts_at->getTimestamp() <= $now)
            && ($this->ends_at === null || $this->ends_at->getTimestamp() > $now);
    }
}
