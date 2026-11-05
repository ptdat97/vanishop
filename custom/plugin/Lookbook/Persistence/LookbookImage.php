<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $lookbook_id
 * @property int $media_id
 * @property array<string, string|null>|null $alt
 * @property int $position
 */
final class LookbookImage extends Model
{
    public $timestamps = false;

    protected $table = 'plg_lookbook_images';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['media_id' => 'integer', 'alt' => 'array', 'position' => 'integer'];
    }
}
