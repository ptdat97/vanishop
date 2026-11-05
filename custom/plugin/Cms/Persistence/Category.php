<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chuyên mục tin tức (1.4.0): một cấp, URL /tin-tuc/{slug} (cùng không gian slug với bài viết).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $sort_order
 */
final class Category extends Model
{
    public const MAX = 50;

    protected $table = 'plg_cms_categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
