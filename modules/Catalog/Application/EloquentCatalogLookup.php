<?php

declare(strict_types=1);

namespace Modules\Catalog\Application;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Shared\Domain\Text\VietnameseText;

final class EloquentCatalogLookup implements CatalogLookup
{
    public function __construct(private readonly CategoryTreeQuery $categories) {}

    public function visible(string $kind, array $ids, string $locale): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $found = match ($kind) {
            'category' => $this->visibleCategories($ids, $locale),
            'collection' => ProductCollection::query()->with('translations')->whereIn('id', $ids)->where('status', 'active')->get()
                ->map(fn (ProductCollection $collection): array => ['id' => $collection->id, 'slug' => $collection->slugFor($locale), 'name' => (string) $collection->translate('name', $locale)])->all(),
            'brand' => Brand::query()->whereIn('id', $ids)->where('status', Brand::ACTIVE)->get()
                ->map(fn (Brand $brand): array => ['id' => $brand->id, 'slug' => $brand->slug, 'name' => $brand->name])->all(),
            'product' => $this->visibleProducts($ids, $locale),
            default => throw new InvalidArgumentException("Loại catalog [{$kind}] không hỗ trợ."),
        };

        return array_column($found, null, 'id');
    }

    public function search(string $kind, string $query, string $locale, int $limit = 20): array
    {
        $tokens = VietnameseText::tokens($query);

        return match ($kind) {
            'category' => $this->searchByTranslation(Category::query(), $tokens, $locale, $limit, fn (Category $category): bool => $category->status->value === 'active'),
            'collection' => $this->searchByTranslation(ProductCollection::query(), $tokens, $locale, $limit, fn (ProductCollection $collection): bool => $collection->status === 'active'),
            'brand' => Brand::query()
                ->when($tokens !== [], fn (Builder $builder) => $builder->where(fn (Builder $where) => $where->where('name', 'like', '%'.addcslashes(trim($query), '%_\\').'%')->orWhere('code', 'like', '%'.addcslashes(trim($query), '%_\\').'%')))
                ->orderBy('name')->limit($limit)->get()
                ->map(fn (Brand $brand): array => ['id' => $brand->id, 'name' => $brand->name, 'visible' => $brand->status === Brand::ACTIVE])->all(),
            'product' => Style::query()->with('translations')
                ->when($tokens !== [], function (Builder $builder) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $builder->where('search_text', 'like', '%'.addcslashes($token, '%_\\').'%');
                    }
                })
                ->orderByDesc('updated_at')->limit($limit)->get()
                ->map(fn (Style $style): array => [
                    'id' => $style->id,
                    'name' => trim(($style->translate('name', $locale) ?? '').' · '.$style->style_code, ' ·'),
                    'visible' => PublishWindow::isVisible($style->status, $style->publishWindow(), new \DateTimeImmutable),
                ])->all(),
            default => throw new InvalidArgumentException("Loại catalog [{$kind}] không hỗ trợ."),
        };
    }

    /**
     * Danh mục hiển thị = chính nó và mọi danh mục cha đang hiện (cùng quy tắc với cây danh mục storefront).
     *
     * @param  list<int>  $ids
     * @return list<array{id: int, slug: string, name: string}>
     */
    private function visibleCategories(array $ids, string $locale): array
    {
        $wanted = array_flip($ids);
        $found = [];
        $walk = function (array $nodes) use (&$walk, &$found, $wanted): void {
            foreach ($nodes as $node) {
                if (isset($wanted[(int) $node['id']])) {
                    $found[] = ['id' => (int) $node['id'], 'slug' => (string) $node['slug'], 'name' => (string) $node['name']];
                }
                $walk($node['children'] ?? []);
            }
        };
        $walk($this->categories->tree(activeOnly: true, locale: $locale));

        return $found;
    }

    /**
     * @param  list<int>  $ids
     * @return list<array{id: int, slug: string, name: string}>
     */
    private function visibleProducts(array $ids, string $locale): array
    {
        $now = new \DateTimeImmutable;

        return Style::query()->with('translations')->whereIn('id', $ids)->get()
            ->filter(fn (Style $style): bool => PublishWindow::isVisible($style->status, $style->publishWindow(), $now))
            ->map(fn (Style $style): array => ['id' => $style->id, 'slug' => $style->slugFor($locale), 'name' => (string) $style->translate('name', $locale)])
            ->values()->all();
    }

    /**
     * Tìm trên bản dịch `name` (danh mục, bộ sưu tập) — so khớp không dấu theo từng từ.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $builder
     * @param  list<string>  $tokens
     * @param  callable(mixed): bool  $visible
     * @return list<array{id: int, name: string, visible: bool}>
     */
    private function searchByTranslation(Builder $builder, array $tokens, string $locale, int $limit, callable $visible): array
    {
        $rows = $builder->with('translations')->orderBy('id')->get()
            ->map(fn ($model): array => ['id' => (int) $model->id, 'name' => (string) ($model->translate('name', $locale) ?? $model->slug), 'visible' => $visible($model)])
            ->filter(function (array $row) use ($tokens): bool {
                $haystack = implode(' ', VietnameseText::tokens($row['name']));
                foreach ($tokens as $token) {
                    if (! str_contains($haystack, $token)) {
                        return false;
                    }
                }

                return true;
            });

        return array_slice($rows->values()->all(), 0, $limit);
    }
}
