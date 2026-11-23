<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\NormalizesBlockConfiguration;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Sản phẩm theo tab (reference-parity §3.16-4): tối đa TABS tab, mỗi tab một nguồn (mới nhất, danh mục, bộ sưu tập,
 * thương hiệu, chọn tay). HTML xếp dọc các tab, JS chuyển thành tab. Tab không có sản phẩm bị bỏ.
 */
final class ProductTabsBlock implements NormalizesBlockConfiguration
{
    public const TABS = 4;

    public function __construct(private readonly Products $products) {}

    public function type(): string
    {
        return 'extras_product_tabs';
    }

    public function label(): string
    {
        return __('vani-storefront-extras::texts.product_tabs_block.products_tab');
    }

    public function fields(): array
    {
        $fields = [FieldDefinition::string('title', __('vani-storefront-extras::texts.product_tabs_block.title'), max: 120), FieldDefinition::int('limit', __('vani-storefront-extras::texts.product_tabs_block.products_per_tab_1_24'), help: __('vani-storefront-extras::texts.product_tabs_block.default_8'))];
        $fields[] = FieldDefinition::repeater('tabs', 'vani-storefront-extras::texts.repeater.tabs', [
            FieldDefinition::string('label', 'vani-storefront-extras::texts.repeater.title', max: 60),
            FieldDefinition::select('source', 'vani-storefront-extras::texts.repeater.source', Products::SOURCES),
            FieldDefinition::string('value', 'vani-storefront-extras::texts.repeater.source_value', max: 300),
        ], max: self::TABS);

        return $fields;
    }

    public function normalize(array $config): array
    {
        return RepeaterRows::normalize($config, 'tabs', 'tab', self::TABS, ['label', 'source', 'value']);
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(24, (int) ($config['limit'] ?? 8) ?: 8));
        $tabs = [];
        foreach (($this->normalize($config)['tabs'] ?? []) as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $source = (string) ($row['source'] ?? '');
            if ($label === '' || ! isset(Products::SOURCES[$source])) {
                continue;
            }
            $products = $this->products->from($source, (string) ($row['value'] ?? ''), $limit, $locale);
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
