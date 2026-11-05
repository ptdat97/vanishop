<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Illuminate\Support\Str;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Nội dung giàu định dạng (reference-parity §3.16-4): Markdown, bỏ HTML thô và link không an toàn (`javascript:`…) —
 * người soạn không chèn được script. Khác khối "Nội dung" của Core (chữ thường).
 */
final class MarkdownBlock implements StorefrontBlock
{
    public function type(): string
    {
        return 'extras_markdown';
    }

    public function label(): string
    {
        return 'Nội dung Markdown';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', 'Tiêu đề', max: 120),
            FieldDefinition::text('body', 'Nội dung (Markdown)', required: true, help: 'Đậm **chữ**, nghiêng *chữ*, danh sách "- ", link [chữ](https://…). HTML bị bỏ.', max: 5000),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        return [
            'title' => (string) ($config['title'] ?? ''),
            'html' => Str::markdown((string) ($config['body'] ?? ''), ['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 20]),
        ];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.markdown';
    }
}
