<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Storefront\Contracts\LinkType;

/**
 * Link tới đối tượng catalog theo id (giá trị = id, đổi slug link vẫn đúng). Mỗi lô một truy vấn qua `CatalogLookup`.
 */
abstract class CatalogLinkType implements LinkType
{
    public function __construct(protected readonly CatalogLookup $catalog) {}

    /** @return 'category'|'collection'|'brand'|'product' */
    abstract protected function kind(): string;

    abstract protected function url(string $slug): string;

    public function type(): string
    {
        return $this->kind();
    }

    public function searchable(): bool
    {
        return true;
    }

    public function options(): array
    {
        return [];
    }

    public function search(string $query, string $locale, int $limit = 20): array
    {
        return array_map(
            fn (array $row): array => ['value' => (string) $row['id'], 'label' => $row['visible'] ? $row['name'] : "{$row['name']} (đang ẩn)"],
            $this->catalog->search($this->kind(), $query, $locale, $limit),
        );
    }

    public function accepts(string $value): bool
    {
        return ctype_digit($value) && (int) $value > 0;
    }

    public function resolve(array $values, string $locale): array
    {
        $ids = array_map('intval', array_filter($values, $this->accepts(...)));
        $resolved = [];
        foreach ($this->catalog->visible($this->kind(), $ids, $locale) as $id => $item) {
            $resolved[(string) $id] = ['url' => $this->url($item['slug']), 'label' => $item['name']];
        }

        return $resolved;
    }
}
