<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Database\Eloquent\Builder;
use Modules\Storefront\Contracts\LinkType;
use Plugin\Cms\Persistence\Category;

/**
 * Link tới chuyên mục tin tức theo id (1.4.0, reference-parity §3.17-3). Chuyên mục đã xoá → mục menu bị ẩn.
 */
final class ArticleCategoryLinkType implements LinkType
{
    public function type(): string
    {
        return 'article_category';
    }

    public function label(): string
    {
        return 'Chuyên mục tin tức';
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
        return Category::query()
            ->when(trim($query) !== '', fn (Builder $builder) => $builder->where('name', 'like', '%'.addcslashes(trim($query), '%_\\').'%'))
            ->orderBy('sort_order')->orderBy('name')->limit($limit)->get()
            ->map(fn (Category $category): array => ['value' => (string) $category->id, 'label' => $category->name])->all();
    }

    public function accepts(string $value): bool
    {
        return ctype_digit($value) && (int) $value > 0;
    }

    public function resolve(array $values, string $locale): array
    {
        $ids = array_map('intval', array_filter($values, $this->accepts(...)));

        return $ids === [] ? [] : Category::query()->whereIn('id', $ids)->get()
            ->mapWithKeys(fn (Category $category): array => [(string) $category->id => ['url' => vani_route('storefront.p.vani-cms.post', $category->slug, $locale), 'label' => $category->name]])
            ->all();
    }
}
