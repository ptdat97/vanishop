<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Nội dung chung của trang và bài viết. Công khai khi `status = published` và `published_at` đã tới (hẹn giờ đăng).
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $body
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 */
abstract class Content extends Model
{
    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublic(): bool
    {
        return $this->status === self::PUBLISHED && $this->published_at !== null && $this->published_at->lte(now());
    }
}
