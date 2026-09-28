<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $media_id
 * @property string $role
 * @property int $position
 * @property string|null $alt
 * @property Media $media
 */
final class Mediable extends Model
{
    protected $fillable = ['media_id', 'mediable_type', 'mediable_id', 'role', 'position', 'alt'];

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
