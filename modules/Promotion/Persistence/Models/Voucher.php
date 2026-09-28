<?php

declare(strict_types=1);

namespace Modules\Promotion\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Phạm vi brand kế thừa từ promotion (truy vấn luôn đi qua promotion có BelongsToBrand).
 *
 * @property int $id
 * @property int $promotion_id
 * @property string $code
 * @property int|null $usage_limit
 * @property int $used_count
 * @property Carbon|null $expires_at
 * @property string $status
 */
final class Voucher extends Model
{
    protected $fillable = ['promotion_id', 'code', 'usage_limit', 'used_count', 'expires_at', 'status'];

    protected function casts(): array
    {
        return ['promotion_id' => 'integer', 'usage_limit' => 'integer', 'used_count' => 'integer', 'expires_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Promotion, $this>
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public static function normalize(string $code): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $code));
    }
}
