<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Lưới banner 1–4 ô (reference-parity §3.16-4): bố cục chọn sẵn; ô thiếu ảnh bị bỏ.
 */
final class BannerGridBlock implements StorefrontBlock
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
        foreach (range(1, self::CELLS) as $i) {
            $fields[] = FieldDefinition::media("cell_{$i}_media", __('vani-storefront-extras::texts.banner_grid_block.tile_i_image', ['i' => $i]), help: __('vani-storefront-extras::texts.banner_grid_block.from_media_library_responsive_versions'));
            $fields[] = FieldDefinition::string("cell_{$i}_title", __('vani-storefront-extras::texts.banner_grid_block.tile_i_title', ['i' => $i]), max: 120);
            $fields[] = FieldDefinition::string("cell_{$i}_link", __('vani-storefront-extras::texts.banner_grid_block.tile_i_link', ['i' => $i]), max: 300);
        }

        return $fields;
    }

    public function resolve(array $config, string $locale): array
    {
        $layout = in_array((string) ($config['layout'] ?? '2'), ['1', '2', '3', '4'], true) ? (int) $config['layout'] : 2;
        $cells = [];
        foreach (range(1, $layout) as $i) {
            [$image, $srcset] = Products::image($config["cell_{$i}_media"] ?? null, $config["cell_{$i}_image"] ?? '', 1200);
            if ($image !== '') {
                // Ô lớn: bố cục 1 ô, hoặc ô đầu của bố cục 3 ô (chiếm 2 cột × 2 hàng) — rộng cả khung; còn lại nửa khung.
                $wide = $layout === 1 || ($layout === 3 && $cells === []);
                $link = Products::safeUrl($config["cell_{$i}_link"] ?? '');
                $cells[] = [
                    'image' => $image, 'srcset' => $srcset !== '' ? $srcset : null, 'title' => (string) ($config["cell_{$i}_title"] ?? ''),
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
