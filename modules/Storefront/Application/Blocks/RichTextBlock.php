<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Illuminate\Support\HtmlString;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Đoạn văn bản thuần (escape khi render, xuống dòng giữ nguyên) — không nhận HTML để tránh XSS.
 */
final class RichTextBlock implements StorefrontBlock
{
    public const MAX_TEXT_LENGTH = 5000;

    public function type(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return 'Đoạn văn bản';
    }

    public function fields(): array
    {
        return [FieldDefinition::string('title', 'Tiêu đề', max: 120), FieldDefinition::text('body', 'Nội dung', required: true, max: 50_000)];
    }

    public function resolve(array $config, string $locale): array
    {
        $body = (string) ($config['body'] ?? '');

        return [
            'title' => (string) ($config['title'] ?? ''),
            'bodyHtml' => new HtmlString(RichTextRenderer::render($body)),
        ];
    }

    public function view(): string
    {
        return 'theme::blocks.rich_text';
    }
}
