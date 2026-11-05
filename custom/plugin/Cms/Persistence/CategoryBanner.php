<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * Banner đầu trang danh mục sản phẩm (1.5.0): nội dung theo ngôn ngữ (tiêu đề, Markdown ngắn, chữ trên nút), link nút,
 * ảnh desktop/mobile từ Thư viện ảnh.
 *
 * @property int $id
 * @property int $category_id
 * @property array<string, array{title?: ?string, markdown?: ?string, button_text?: ?string}>|null $content
 * @property string|null $button_url
 * @property int|null $desktop_media_id
 * @property int|null $mobile_media_id
 * @property bool $active
 */
final class CategoryBanner extends Model
{
    protected $table = 'plg_cms_category_banners';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['category_id' => 'integer', 'content' => 'array', 'desktop_media_id' => 'integer', 'mobile_media_id' => 'integer', 'active' => 'boolean'];
    }

    /**
     * Văn bản theo ngôn ngữ, thiếu thì dùng ngôn ngữ mặc định.
     */
    public function text(string $field, string $locale, string $default): ?string
    {
        return ($this->content[$locale][$field] ?? null) ?: (($this->content[$default][$field] ?? null) ?: null);
    }
}
