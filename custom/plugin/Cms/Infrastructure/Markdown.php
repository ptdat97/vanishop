<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Markdown (GFM: bảng, gạch ngang, link tự động) → HTML an toàn: HTML thô trong nội dung bị bỏ, link
 * `javascript:`/`data:` bị chặn — người soạn không chèn được script vào storefront.
 */
final class Markdown
{
    private ?GithubFlavoredMarkdownConverter $converter = null;

    public function html(string $markdown): string
    {
        $this->converter ??= new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);

        return (string) $this->converter->convert($markdown);
    }

    /** Đoạn văn bản thuần (tóm tắt, meta description). */
    public function plain(string $markdown, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->html($markdown)), ENT_QUOTES | ENT_HTML5)));

        return Str::limit($text, $limit, '…');
    }
}
