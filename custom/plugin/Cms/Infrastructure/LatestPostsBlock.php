<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Cms\Persistence\Post;

/**
 * Khối page builder: bài viết mới nhất.
 */
final class LatestPostsBlock implements StorefrontBlock
{
    public function __construct(private readonly ContentPresenter $presenter) {}

    public function type(): string
    {
        return 'cms_latest_posts';
    }

    public function label(): string
    {
        return 'Bài viết mới';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', 'Tiêu đề', max: 120),
            FieldDefinition::int('limit', 'Số bài (1–12)', required: true),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(12, (int) ($config['limit'] ?? 3)));

        return [
            'title' => (string) ($config['title'] ?? ''),
            'posts' => Post::query()->published()->orderByDesc('published_at')->limit($limit)->get()
                ->map(fn (Post $post): array => $this->presenter->postSummary($post))->all(),
        ];
    }

    public function view(): string
    {
        return 'vani-cms::blocks.latest-posts';
    }
}
