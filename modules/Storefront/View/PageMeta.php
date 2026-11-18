<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

use Illuminate\Support\Str;

/**
 * Tiêu đề/mô tả/ảnh chia sẻ của một trang storefront, có giá trị dự phòng — dựng sẵn cho theme (`$meta`), theme chỉ in.
 * Tiêu đề: `meta_title` → tên. Mô tả: `meta_description` → đoạn đầu mô tả (bỏ HTML, ≤ 160 ký tự) → null (layout dùng
 * mô tả cửa hàng). Ảnh: ảnh của trang → null (layout dùng ảnh chia sẻ mặc định của cửa hàng).
 */
final class PageMeta
{
    public const DESCRIPTION_LIMIT = 160;

    /**
     * Danh mục, thương hiệu… (mảng có `name`, `meta_title`, `meta_description`, `description`).
     *
     * @param  array<string, mixed>  $entity
     * @return array{title: string, description: string|null, image: string|null}
     */
    public static function for(array $entity, ?string $image = null): array
    {
        return [
            'title' => self::title($entity['meta_title'] ?? null, (string) ($entity['name'] ?? '')),
            'description' => self::description($entity['meta_description'] ?? null, $entity['description'] ?? null),
            'image' => self::absoluteUrl($image),
        ];
    }

    /**
     * URL tuyệt đối cho thẻ chia sẻ và JSON-LD (Facebook, Zalo, Google không nhận đường dẫn tương đối như `/storage/…`).
     */
    public static function absoluteUrl(mixed $url): ?string
    {
        $url = self::filled($url);

        return $url === null || preg_match('#^https?://#i', $url) === 1 ? $url : url($url);
    }

    public static function title(mixed $metaTitle, string $name): string
    {
        return self::filled($metaTitle) ?? $name;
    }

    public static function description(mixed $metaDescription, mixed $text): ?string
    {
        if (($meta = self::filled($metaDescription)) !== null) {
            return $meta;
        }
        $plain = self::filled(preg_replace('/\s+/u', ' ', strip_tags((string) (is_string($text) ? $text : ''))));

        return $plain === null ? null : Str::limit($plain, self::DESCRIPTION_LIMIT);
    }

    private static function filled(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
