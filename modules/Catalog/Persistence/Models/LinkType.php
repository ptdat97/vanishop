<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Persistence\Database\Factories\LinkTypeFactory;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Loại liên kết giữa các sản phẩm (tương tự, mua kèm, nâng cấp…), tên hiển thị trên PDP theo ngôn ngữ.
 *
 * @property int $id
 * @property string $code
 * @property bool $is_active
 * @property int $position
 */
final class LinkType extends Model
{
    /** @use HasFactory<LinkTypeFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['code', 'is_active', 'position'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    protected function translationModel(): string
    {
        return LinkTypeTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    /**
     * @return HasMany<StyleLinkGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(StyleLinkGroup::class);
    }

    protected static function newFactory(): LinkTypeFactory
    {
        return LinkTypeFactory::new();
    }
}
