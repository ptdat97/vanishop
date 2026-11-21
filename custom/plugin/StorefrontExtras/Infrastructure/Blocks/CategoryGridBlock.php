<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

final class CategoryGridBlock implements StorefrontBlock
{
    private const SLOTS = 6;

    public function __construct(private readonly CatalogReader $catalog) {}

    public function type(): string
    {
        return 'extras_category_grid';
    }

    public function label(): string
    {
        return __('vani-storefront-extras::storefront.category_grid');
    }

    public function fields(): array
    {
        $choices = array_column($this->categories(app()->getLocale()), 'name', 'id');
        $fields = [FieldDefinition::string('title', __('vani-storefront-extras::texts.product_tabs_block.title'), max: 120)];
        foreach (range(1, self::SLOTS) as $position) {
            $fields[] = FieldDefinition::select("category_{$position}", __('vani-storefront-extras::storefront.category_position', ['position' => $position]), $choices);
        }

        return $fields;
    }

    public function resolve(array $config, string $locale): array
    {
        $available = $this->categories($locale);
        $selected = [];
        foreach (range(1, self::SLOTS) as $position) {
            $id = (int) ($config["category_{$position}"] ?? 0);
            if (isset($available[$id])) {
                $selected[$id] = $available[$id];
            }
        }

        return ['title' => (string) ($config['title'] ?? ''), 'categories' => array_values($selected)];
    }

    /** @return array<int, array{id: int, name: string, slug: string, image_url: ?string}> */
    private function categories(string $locale): array
    {
        $categories = [];
        $visit = function (array $nodes) use (&$visit, &$categories): void {
            foreach ($nodes as $node) {
                $categories[$node['id']] = array_intersect_key($node, array_flip(['id', 'name', 'slug', 'image_url']));
                $visit($node['children']);
            }
        };
        $visit($this->catalog->categoryTree($locale));

        return $categories;
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.category-grid';
    }
}
