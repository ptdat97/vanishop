<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Biểu tượng + chữ (reference-parity §3.16-4): tối đa ITEMS cam kết (giao hàng, đổi trả…). Biểu tượng chọn từ bộ SVG
 * của plugin (không nhận SVG/HTML do người nhập).
 */
final class FeaturesBlock implements StorefrontBlock
{
    public const ITEMS = 4;

    public const ICONS = ['truck' => 'Giao hàng', 'return' => 'Đổi trả', 'shield' => 'Bảo đảm', 'support' => 'Hỗ trợ', 'gift' => 'Quà tặng', 'card' => 'Thanh toán'];

    public function type(): string
    {
        return 'extras_features';
    }

    public function label(): string
    {
        return 'Biểu tượng + chữ';
    }

    public function fields(): array
    {
        $fields = [];
        foreach (range(1, self::ITEMS) as $i) {
            $fields[] = FieldDefinition::select("item_{$i}_icon", "Mục {$i}: biểu tượng", self::ICONS);
            $fields[] = FieldDefinition::string("item_{$i}_title", "Mục {$i}: tiêu đề", max: 60);
            $fields[] = FieldDefinition::string("item_{$i}_text", "Mục {$i}: mô tả", max: 160);
        }

        return $fields;
    }

    public function resolve(array $config, string $locale): array
    {
        $items = [];
        foreach (range(1, self::ITEMS) as $i) {
            $title = trim((string) ($config["item_{$i}_title"] ?? ''));
            if ($title !== '') {
                $icon = (string) ($config["item_{$i}_icon"] ?? '');
                $items[] = ['icon' => isset(self::ICONS[$icon]) ? $icon : 'shield', 'title' => $title, 'text' => (string) ($config["item_{$i}_text"] ?? '')];
            }
        }

        return ['items' => $items];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.features';
    }
}
