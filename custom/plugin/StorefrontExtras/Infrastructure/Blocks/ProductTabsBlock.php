<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Sản phẩm theo tab (reference-parity §3.16-4): tối đa TABS tab, mỗi tab một nguồn (mới nhất, danh mục, bộ sưu tập,
 * thương hiệu, chọn tay). Không JS → các tab xếp dọc; có JS → chuyển tab. Tab không có sản phẩm bị bỏ.
 */
final class ProductTabsBlock implements StorefrontBlock
{
    public const TABS = 4;

    public function __construct(private readonly Products $products) {}

    public function type(): string
    {
        return 'extras_product_tabs';
    }

    public function label(): string
    {
        return 'Sản phẩm theo tab';
    }

    public function fields(): array
    {
        $fields = [FieldDefinition::string('title', 'Tiêu đề', max: 120), FieldDefinition::int('limit', 'Số sản phẩm mỗi tab (1–24)', help: 'Mặc định 8.')];
        foreach (range(1, self::TABS) as $i) {
            $fields[] = FieldDefinition::string("tab_{$i}_label", "Tab {$i}: tên", max: 60);
            $fields[] = FieldDefinition::select("tab_{$i}_source", "Tab {$i}: nguồn", Products::SOURCES);
            $fields[] = FieldDefinition::string("tab_{$i}_value", "Tab {$i}: slug hoặc id", help: 'Slug danh mục/bộ sưu tập/thương hiệu, hoặc id sản phẩm cách nhau bằng dấu phẩy (chọn tay).', max: 300);
        }

        return $fields;
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(24, (int) ($config['limit'] ?? 8) ?: 8));
        $tabs = [];
        foreach (range(1, self::TABS) as $i) {
            $label = trim((string) ($config["tab_{$i}_label"] ?? ''));
            $source = (string) ($config["tab_{$i}_source"] ?? '');
            if ($label === '' || ! isset(Products::SOURCES[$source])) {
                continue;
            }
            $products = $this->products->from($source, (string) ($config["tab_{$i}_value"] ?? ''), $limit, $locale);
            if ($products !== []) {
                $tabs[] = ['label' => $label, 'products' => $products];
            }
        }

        return ['title' => (string) ($config['title'] ?? ''), 'tabs' => $tabs];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.product-tabs';
    }
}
