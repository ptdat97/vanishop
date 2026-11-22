<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Contracts\Data\MediaData;
use Modules\Catalog\Contracts\MediaDirectory;
use Plugin\Cms\Persistence\Post;

/**
 * Ảnh bìa của bài viết: từ Thư viện ảnh dùng chung (`cover_media_id`, 1.1.0); bài tạo ở 1.0 còn `cover_path` (file CMS
 * tự lưu trong thư mục `cms/` của disk media) thì vẫn hiển thị.
 */
final class CmsMedia
{
    public const POST = 'plg.cms.post';

    public const PAGE = 'plg.cms.page';

    public function __construct(private readonly MediaDirectory $media) {}

    public function cover(Post $post): ?MediaData
    {
        $id = $post->cover_media_id;

        return $id === null ? null : ($this->media->find([$id])[$id] ?? null);
    }

    public function coverUrl(Post $post, int $width = 1600): ?string
    {
        if ($post->cover_media_id !== null) {
            return $this->cover($post)?->urlFor($width);
        }

        return $post->cover_path === null || $post->cover_path === '' ? null : Storage::disk((string) config('vanishop.media.disk', 'public'))->url($post->cover_path);
    }
}
