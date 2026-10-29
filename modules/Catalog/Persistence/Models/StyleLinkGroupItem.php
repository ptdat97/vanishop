<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $style_link_group_id
 * @property int $style_id
 * @property int $position
 * @property StyleLinkGroup $group
 * @property Style $style
 */
final class StyleLinkGroupItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['style_link_group_id', 'style_id', 'position'];

    protected function casts(): array
    {
        return ['style_link_group_id' => 'integer', 'style_id' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<StyleLinkGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(StyleLinkGroup::class, 'style_link_group_id');
    }

    /**
     * @return BelongsTo<Style, $this>
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }
}
