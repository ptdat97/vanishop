<?php

declare(strict_types=1);

namespace Modules\Channel\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $channel_id
 * @property string $host
 * @property string $path_prefix
 * @property bool $is_primary
 */
final class ChannelDomain extends Model
{
    protected $fillable = ['channel_id', 'host', 'path_prefix', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
