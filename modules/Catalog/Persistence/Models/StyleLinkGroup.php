<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nhóm sản phẩm liên kết cùng loại. Hai chiều (root_style_id = null): mọi thành viên thấy nhau.
 * Một chiều: chỉ style gốc thấy các thành viên còn lại.
 *
 * @property int $id
 * @property int $link_type_id
 * @property int|null $root_style_id
 * @property LinkType $type
 * @property Collection<int, StyleLinkGroupItem> $items
 */
final class StyleLinkGroup extends Model
{
    protected $fillable = ['link_type_id', 'root_style_id'];

    protected function casts(): array
    {
        return ['link_type_id' => 'integer', 'root_style_id' => 'integer'];
    }

    /**
     * @return BelongsTo<LinkType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LinkType::class, 'link_type_id');
    }

    /**
     * @return HasMany<StyleLinkGroupItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StyleLinkGroupItem::class)->orderBy('position')->orderBy('id');
    }

    public function isOneWay(): bool
    {
        return $this->root_style_id !== null;
    }

    /**
     * Style này có thấy các thành viên khác của nhóm không.
     */
    public function isVisibleFrom(int $styleId): bool
    {
        return $this->root_style_id === null || $this->root_style_id === $styleId;
    }
}
