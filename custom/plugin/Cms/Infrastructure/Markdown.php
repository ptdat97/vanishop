<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Support\Str;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Modules\Catalog\Contracts\MediaDirectory;

/**
 * Markdown (GFM: bảng, gạch ngang, link tự động) → HTML an toàn: HTML thô trong nội dung bị bỏ, link
 * `javascript:`/`data:` bị chặn — người soạn không chèn được script vào storefront.
 *
 * Ảnh từ Thư viện ảnh viết `![mô tả](media:123)`: lúc render mới đổi sang URL hiện hành + `srcset` (URL bản thu nhỏ đổi
 * khi bật/tắt WebP hay đổi disk, nên không lưu URL vào bài). Ảnh đã bị xoá khỏi thư viện thì bị bỏ khỏi bài.
 */
final class Markdown
{
    /** Ảnh trong bài: cột nội dung storefront ~720px → bản 800 (srcset cho màn hình mật độ cao). */
    private const IMAGE_WIDTH = 800;

    private const MEDIA_REF = '/\bmedia:(\d+)\b/';

    private ?GithubFlavoredMarkdownConverter $converter = null;

    public function __construct(private readonly MediaDirectory $media) {}

    public function html(string $markdown): string
    {
        $this->converter ??= $this->makeConverter();

        return (string) $this->converter->convert($markdown);
    }

    /** Đoạn văn bản thuần (tóm tắt, meta description). */
    public function plain(string $markdown, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->html($markdown)), ENT_QUOTES | ENT_HTML5)));

        return Str::limit($text, $limit, '…');
    }

    /**
     * Id ảnh thư viện được chèn trong bài (để khai báo "đang dùng"), theo thứ tự xuất hiện.
     *
     * @return list<int>
     */
    public static function mediaIds(string $markdown): array
    {
        preg_match_all(self::MEDIA_REF, $markdown, $matches);

        return array_values(array_unique(array_map('intval', $matches[1])));
    }

    private function makeConverter(): GithubFlavoredMarkdownConverter
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);
        $converter->getEnvironment()->addEventListener(DocumentParsedEvent::class, $this->resolveLibraryImages(...));

        return $converter;
    }

    private function resolveLibraryImages(DocumentParsedEvent $event): void
    {
        $images = [];
        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Image && preg_match('/^media:(\d+)$/', $node->getUrl(), $match) === 1) {
                $images[] = [$node, (int) $match[1]];
            }
        }
        if ($images === []) {
            return;
        }

        $found = $this->media->find(array_column($images, 1));
        foreach ($images as [$image, $id]) {
            $media = $found[$id] ?? null;
            if ($media === null) {
                $image->detach();

                continue;
            }
            $image->setUrl($media->urlFor(self::IMAGE_WIDTH));
            $image->data->set('attributes/loading', 'lazy');
            if ($media->width !== null && $media->height !== null) {
                $image->data->set('attributes/width', (string) $media->width);
                $image->data->set('attributes/height', (string) $media->height);
            }
            if ($media->srcset() !== '') {
                $image->data->set('attributes/srcset', $media->srcset());
                $image->data->set('attributes/sizes', '(min-width: 768px) 720px, 100vw');
            }
        }
    }
}
