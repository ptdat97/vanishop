<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $excerpt
 * @property string|null $cover_path ảnh bìa tải riêng (CMS 1.0), chỉ còn để hiển thị bài cũ
 * @property int|null $cover_media_id ảnh bìa từ Thư viện ảnh
 * @property int|null $category_id chuyên mục (1.4.0)
 * @property list<int>|null $product_ids mẫu sản phẩm gắn cuối bài (1.4.0, tối đa MAX_PRODUCTS)
 */
final class Post extends Content
{
    public const MAX_PRODUCTS = 12;

    protected $table = 'plg_cms_posts';

    protected function casts(): array
    {
        return [...parent::casts(), 'cover_media_id' => 'integer', 'category_id' => 'integer', 'product_ids' => 'array'];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
