<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Trình chiếu ảnh + chữ (reference-parity §3.16-4): tối đa SLIDES slide; trượt ngang bằng CSS scroll-snap,
 * JS tự chuyển theo `interval`. Slide thiếu ảnh bị bỏ.
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
        return __('vani-storefront-extras::texts.slideshow_block.image_slideshow');
    }

    public function fields(): array
    {
        $fields = [FieldDefinition::int('interval', __('vani-storefront-extras::texts.slideshow_block.auto_advance_after_seconds'), help: __('vani-storefront-extras::texts.slideshow_block.text_0_no_auto_advance_default'))];
        foreach (range(1, self::SLIDES) as $i) {
            $fields[] = FieldDefinition::media("slide_{$i}_media", __('vani-storefront-extras::texts.slideshow_block.slide_i_image', ['i' => $i]), help: __('vani-storefront-extras::texts.slideshow_block.from_media_library_responsive_versions'));
            $fields[] = FieldDefinition::string("slide_{$i}_title", __('vani-storefront-extras::texts.slideshow_block.slide_i_title', ['i' => $i]), max: 120);
            $fields[] = FieldDefinition::string("slide_{$i}_text", __('vani-storefront-extras::texts.slideshow_block.slide_i_description', ['i' => $i]), max: 200);
            $fields[] = FieldDefinition::string("slide_{$i}_link", __('vani-storefront-extras::texts.slideshow_block.slide_i_link', ['i' => $i]), max: 300);
            $fields[] = FieldDefinition::string("slide_{$i}_label", __('vani-storefront-extras::texts.slideshow_block.slide_i_button_text', ['i' => $i]), max: 40);
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
