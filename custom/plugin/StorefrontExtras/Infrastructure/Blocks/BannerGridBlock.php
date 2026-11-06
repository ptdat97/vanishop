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

    public function type(): string
    {
        return 'extras_banner_grid';
    }

    public function label(): string
    {
        return 'Lưới banner';
    }

    public function fields(): array
    {
        $fields = [FieldDefinition::select('layout', 'Bố cục', ['1' => '1 ô', '2' => '2 ô ngang', '3' => '3 ô (1 lớn + 2 nhỏ)', '4' => '4 ô'], required: true)];
        foreach (range(1, self::CELLS) as $i) {
            $fields[] = FieldDefinition::media("cell_{$i}_media", "Ô {$i}: ảnh", help: 'Từ Thư viện ảnh; trống thì dùng ảnh (URL).');
            $fields[] = FieldDefinition::string("cell_{$i}_image", "Ô {$i}: ảnh (URL)", max: 500);
            $fields[] = FieldDefinition::string("cell_{$i}_title", "Ô {$i}: tiêu đề", max: 120);
            $fields[] = FieldDefinition::string("cell_{$i}_link", "Ô {$i}: đường dẫn", max: 300);
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
                $cells[] = ['image' => $image, 'srcset' => $srcset, 'title' => (string) ($config["cell_{$i}_title"] ?? ''), 'link' => Products::safeUrl($config["cell_{$i}_link"] ?? '')];
            }
        }

        return ['layout' => $layout, 'cells' => $cells];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.banner-grid';
    }
}
