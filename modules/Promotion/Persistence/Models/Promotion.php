<?php

declare(strict_types=1);

namespace Modules\Promotion\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Modules\Promotion\Domain\Stacking;
use Modules\Shared\Domain\Schedule\WeeklySchedule;
use Modules\Shared\Support\StoreClock;

/**
 * @property int $id
 * @property string $name
 * @property int|null $campaign_id campaign sở hữu lịch chạy (0.3.36)
 * @property string|null $managed_by
 * @property string|null $managed_key
 * @property string|null $managed_route
 * @property string $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property list<array{days: list<int>, from: string, to: string}>|null $recurrence lịch lặp theo tuần (0.5.116)
 * @property int $priority
 * @property Stacking $stacking
 * @property bool $requires_voucher
 * @property string $action_type
 * @property array<string, mixed> $action_config
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property int|null $usage_limit_per_customer lượt tối đa mỗi khách (0.5.109)
 * @property int|null $budget_amount
 * @property int $budget_used_amount
 * @property int $lock_version
 */
final class Promotion extends Model
{
    protected $fillable = ['campaign_id', 'managed_by', 'managed_key', 'managed_route', 'name', 'status', 'starts_at', 'ends_at', 'recurrence', 'priority', 'stacking', 'requires_voucher', 'action_type', 'action_config',
        'usage_limit', 'usage_count', 'usage_limit_per_customer', 'budget_amount', 'budget_used_amount', 'lock_version'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'recurrence' => 'array', 'priority' => 'integer', 'stacking' => Stacking::class,
            'requires_voucher' => 'boolean', 'action_config' => 'array', 'usage_limit' => 'integer', 'usage_count' => 'integer', 'usage_limit_per_customer' => 'integer',
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

    /** Do plugin quản lý qua ManagedPromotions: Admin chỉ xem. */
    public function isManaged(): bool
    {
        return $this->managed_by !== null;
    }

    /** Liên kết sang màn hình sửa của plugin; route không còn (plugin tắt) → null. */
    public function managedUrl(): ?string
    {
        return $this->managed_route !== null && $this->managed_key !== null && Route::has($this->managed_route)
            ? route($this->managed_route, [$this->managed_key])
            : null;
    }

    public function isRunningAt(int $now): bool
    {
        return $this->status === 'active'
            && ($this->starts_at === null || $this->starts_at->getTimestamp() <= $now)
            && ($this->ends_at === null || $this->ends_at->getTimestamp() > $now)
            && (WeeklySchedule::fromArray($this->recurrence)?->isOpenAt($now, StoreClock::zone()) ?? true);
    }
}
