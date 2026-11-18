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

    /** Đường SVG (viewBox 24) của từng biểu tượng — view chỉ in `icon_path`. */
    private const ICON_PATHS = [
        'truck' => 'M3 7h11v8H3zM14 10h4l3 3v2h-7zM7 18a1.5 1.5 0 1 0 0-.01M17 18a1.5 1.5 0 1 0 0-.01',
        'return' => 'M4 9h11a5 5 0 0 1 0 10H8M4 9l4-4M4 9l4 4',
        'shield' => 'M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z M9 12l2 2 4-4',
        'support' => 'M4 13v-1a8 8 0 0 1 16 0v1M4 13h3v5H4zM17 13h3v5h-3z',
        'gift' => 'M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7c-2-4-6-3-5 0M12 7c2-4 6-3 5 0',
        'card' => 'M3 6h18v12H3zM3 10h18M7 15h4',
    ];

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
                $icon = isset(self::ICONS[$icon]) ? $icon : 'shield';
                $items[] = ['icon' => $icon, 'icon_path' => self::ICON_PATHS[$icon], 'title' => $title, 'text' => (string) ($config["item_{$i}_text"] ?? '')];
            }
        }

        return ['items' => $items];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.features';
    }
}
