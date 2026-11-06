<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Trình chiếu ảnh + chữ (reference-parity §3.16-4): tối đa SLIDES slide; không JS → trượt ngang bằng CSS scroll-snap,
 * có JS → tự chuyển theo `interval`. Slide thiếu ảnh bị bỏ.
 */
final class SlideshowBlock implements StorefrontBlock
{
    public const SLIDES = 5;

    public function type(): string
    {
        return 'extras_slideshow';
    }

    public function label(): string
    {
        return 'Trình chiếu ảnh';
    }

    public function fields(): array
    {
        $fields = [FieldDefinition::int('interval', 'Tự chuyển sau (giây)', help: '0 = không tự chuyển. Mặc định 6.')];
        foreach (range(1, self::SLIDES) as $i) {
            $fields[] = FieldDefinition::media("slide_{$i}_media", "Slide {$i}: ảnh", help: 'Từ Thư viện ảnh (tự có bản vừa màn hình); trống thì dùng ảnh (URL).');
            $fields[] = FieldDefinition::string("slide_{$i}_image", "Slide {$i}: ảnh (URL)", max: 500);
            $fields[] = FieldDefinition::string("slide_{$i}_title", "Slide {$i}: tiêu đề", max: 120);
            $fields[] = FieldDefinition::string("slide_{$i}_text", "Slide {$i}: mô tả", max: 200);
            $fields[] = FieldDefinition::string("slide_{$i}_link", "Slide {$i}: đường dẫn", max: 300);
            $fields[] = FieldDefinition::string("slide_{$i}_label", "Slide {$i}: chữ trên nút", max: 40);
        }

        return $fields;
    }

    public function resolve(array $config, string $locale): array
    {
        $slides = [];
        foreach (range(1, self::SLIDES) as $i) {
            [$image, $srcset] = Products::image($config["slide_{$i}_media"] ?? null, $config["slide_{$i}_image"] ?? '', 1600);
            if ($image !== '') {
                $slides[] = [
                    'image' => $image, 'srcset' => $srcset, 'title' => (string) ($config["slide_{$i}_title"] ?? ''), 'text' => (string) ($config["slide_{$i}_text"] ?? ''),
                    'link' => Products::safeUrl($config["slide_{$i}_link"] ?? ''), 'label' => (string) ($config["slide_{$i}_label"] ?? ''),
                ];
            }
        }

        return ['slides' => $slides, 'interval' => max(0, min(30, (int) ($config['interval'] ?? 6)))];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.slideshow';
    }
}
