<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\NormalizesBlockConfiguration;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Lưới banner 1–4 ô (reference-parity §3.16-4): bố cục chọn sẵn; ô thiếu ảnh bị bỏ.
 */
final class BannerGridBlock implements NormalizesBlockConfiguration
{
    public const CELLS = 4;

    /** Bề rộng khung nội dung của theme (px). */
    private const CONTAINER = 1152;

    public function type(): string
    {
        return 'extras_banner_grid';
    }

    public function label(): string
    {
        return __('vani-storefront-extras::texts.banner_grid_block.banner_grid');
    }

    public function fields(): array
    {
        $fields = [FieldDefinition::select('layout', __('vani-storefront-extras::texts.banner_grid_block.layout'), ['1' => __('vani-storefront-extras::texts.banner_grid_block.text_1_tile'), '2' => __('vani-storefront-extras::texts.banner_grid_block.text_2_tiles_side_side'), '3' => __('vani-storefront-extras::texts.banner_grid_block.text_3_tiles_1_large_2'), '4' => __('vani-storefront-extras::texts.banner_grid_block.text_4_tiles')], required: true)];
        $fields[] = FieldDefinition::repeater('cells', 'vani-storefront-extras::texts.repeater.cells', [
            FieldDefinition::media('media', 'vani-storefront-extras::texts.repeater.image'),
            new FieldDefinition('image', 'vani-storefront-extras::texts.repeater.image_url', max: 2048, editable: false),
            FieldDefinition::string('title', 'vani-storefront-extras::texts.repeater.title', max: 120),
            FieldDefinition::string('link', 'vani-storefront-extras::texts.repeater.link', max: 300),
        ], max: self::CELLS);

        return $fields;
    }

    public function normalize(array $config): array
    {
        return RepeaterRows::normalize($config, 'cells', 'cell', self::CELLS, ['media', 'image', 'title', 'link']);
    }

    public function resolve(array $config, string $locale): array
    {
        $layout = in_array((string) ($config['layout'] ?? '2'), ['1', '2', '3', '4'], true) ? (int) $config['layout'] : 2;
        $cells = [];
        foreach (array_slice(($this->normalize($config)['cells'] ?? []), 0, $layout) as $row) {
            [$image, $srcset] = Products::image($row['media'] ?? null, $row['image'] ?? '', 1200);
            if ($image !== '') {
                // Ô lớn: bố cục 1 ô, hoặc ô đầu của bố cục 3 ô (chiếm 2 cột × 2 hàng) — rộng cả khung; còn lại nửa khung.
                $wide = $layout === 1 || ($layout === 3 && $cells === []);
                $link = Products::safeUrl($row['link'] ?? '');
                $cells[] = [
                    'image' => $image, 'srcset' => $srcset !== '' ? $srcset : null, 'title' => (string) ($row['title'] ?? ''),
                    'link' => $link, 'href' => $link !== '' ? $link : '#', 'wide' => $wide,
                    // [khung px, cột desktop, cột mobile] cho partial ảnh của theme (→ `sizes`).
                    'image_layout' => [self::CONTAINER, $wide ? 1 : 2, 1],
                ];
            }
        }

        return ['layout' => $layout, 'cells' => $cells];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.banner-grid';
    }
}
