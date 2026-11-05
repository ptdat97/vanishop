<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Infrastructure;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Lookbook\Persistence\Lookbook;

/**
 * Khối trang chủ "Lookbook" (đặc tả §3.1): lookbook đang bật mới nhất, 1–6.
 */
final class LatestLookbooksBlock implements StorefrontBlock
{
    public function __construct(private readonly LookbookPresenter $presenter) {}

    public function type(): string
    {
        return 'lookbook_latest';
    }

    public function label(): string
    {
        return 'Lookbook';
    }

    public function fields(): array
    {
        return [FieldDefinition::string('title', 'Tiêu đề', max: 120), FieldDefinition::int('limit', 'Số lookbook (1–6)', help: 'Mặc định 3.')];
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(6, (int) ($config['limit'] ?? 3) ?: 3));
        $lookbooks = Lookbook::query()->visible()->with('images')->orderByDesc('id')->limit($limit)->get()->all();

        return ['title' => (string) ($config['title'] ?? ''), 'lookbooks' => $this->presenter->summaries($lookbooks, $locale)];
    }

    public function view(): string
    {
        return 'vani-lookbook::blocks.latest';
    }
}
