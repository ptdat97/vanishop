<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\NormalizesBlockConfiguration;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Trình chiếu ảnh + chữ (reference-parity §3.16-4): tối đa SLIDES slide; trượt ngang bằng CSS scroll-snap,
 * JS tự chuyển theo `interval`. Slide thiếu ảnh bị bỏ.
 */
final class SlideshowBlock implements NormalizesBlockConfiguration
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
        $fields[] = FieldDefinition::repeater('slides', 'vani-storefront-extras::texts.repeater.slides', [
            FieldDefinition::media('media', 'vani-storefront-extras::texts.repeater.image'),
            new FieldDefinition('image', 'vani-storefront-extras::texts.repeater.image_url', max: 2048, editable: false),
            FieldDefinition::string('title', 'vani-storefront-extras::texts.repeater.title', max: 120),
            FieldDefinition::string('text', 'vani-storefront-extras::texts.repeater.text', max: 200),
            FieldDefinition::string('link', 'vani-storefront-extras::texts.repeater.link', max: 300),
            FieldDefinition::string('label', 'vani-storefront-extras::texts.repeater.button', max: 40),
        ], max: self::SLIDES);

        return $fields;
    }

    public function normalize(array $config): array
    {
        return RepeaterRows::normalize($config, 'slides', 'slide', self::SLIDES, ['media', 'image', 'title', 'text', 'link', 'label']);
    }

    public function resolve(array $config, string $locale): array
    {
        $slides = [];
        foreach (($this->normalize($config)['slides'] ?? []) as $row) {
            [$image, $srcset] = Products::image($row['media'] ?? null, $row['image'] ?? '', 1600);
            if ($image !== '') {
                $slides[] = [
                    'image' => $image, 'srcset' => $srcset, 'title' => (string) ($row['title'] ?? ''), 'text' => (string) ($row['text'] ?? ''),
                    'link' => Products::safeUrl($row['link'] ?? ''), 'label' => (string) ($row['label'] ?? ''),
                ];
            }
        }

        return ['slides' => $slides, 'multiple' => count($slides) > 1, 'interval' => max(0, min(30, (int) ($config['interval'] ?? 6)))];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.slideshow';
    }
}
