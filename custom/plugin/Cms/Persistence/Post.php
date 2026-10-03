<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

/**
 * @property string|null $excerpt
 * @property string|null $cover_path
 */
final class Post extends Content
{
    protected $table = 'plg_cms_posts';
}
