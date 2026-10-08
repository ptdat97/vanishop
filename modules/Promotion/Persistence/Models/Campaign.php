<?php

declare(strict_types=1);

namespace Modules\Promotion\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $status draft | active | stopped
 * @property Carbon|null $stopped_at
 * @property int $lock_version
 */
final class Campaign extends Model
{
    public const DRAFT = 'draft';

    public const ACTIVE = 'active';

    public const STOPPED = 'stopped';

    protected $fillable = ['code', 'name', 'description', 'starts_at', 'ends_at', 'status', 'stopped_at', 'lock_version'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'stopped_at' => 'datetime', 'lock_version' => 'integer'];
    }

    /**
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Trạng thái hiển thị: draft | scheduled | running | ended | stopped.
     */
    public function state(?Carbon $now = null): string
    {
        $now ??= now();

        return match (true) {
            $this->status === self::DRAFT => 'draft',
            $this->status === self::STOPPED => 'stopped',
            $now->lt($this->starts_at) => 'scheduled',
            $now->gte($this->ends_at) => 'ended',
            default => 'running',
        };
    }
}
