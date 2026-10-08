<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

/**
 * @property string|null $excerpt
 * @property string|null $cover_path ảnh bìa tải riêng (CMS 1.0), chỉ còn để hiển thị bài cũ
 * @property int|null $cover_media_id ảnh bìa từ Thư viện ảnh
 */
final class Post extends Content
{
    protected $table = 'plg_cms_posts';

    protected function casts(): array
    {
        return [...parent::casts(), 'cover_media_id' => 'integer'];
    }
}
