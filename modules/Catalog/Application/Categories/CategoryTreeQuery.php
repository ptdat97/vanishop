<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Categories;

use Illuminate\Support\Collection;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Mediable;

/**
 * Đọc cây danh mục của các brand trong CurrentContext (1 truy vấn + eager load, dựng cây trong bộ nhớ).
 *
 * @phpstan-type CategoryNode array{id: int, brand_id: int, parent_id: int|null, slug: string, name: string|null, description: string|null, status: string, position: int, depth: int, image_url: string|null, children: list<mixed>}
 */
final class CategoryTreeQuery
{
    /**
     * @return list<CategoryNode>
     */
    public function tree(bool $activeOnly = false, ?string $locale = null): array
    {
        $categories = Category::query()
            ->with(['translations', 'media.media'])
            ->when($activeOnly, fn ($query) => $query->where('status', CategoryStatus::Active))
            ->orderBy('depth')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $this->build($categories, $locale);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<CategoryNode>
     */
    private function build(Collection $categories, ?string $locale): array
    {
        $nodes = [];
        $roots = [];

        foreach ($categories as $category) {
            $nodes[$category->id] = $this->node($category, $locale);
        }

        // Duyệt ngược (sâu nhất, vị trí lớn nhất trước) để con được gắn vào cha đã hoàn chỉnh, giữ đúng thứ tự.
        foreach ($categories->reverse() as $category) {
            $parentId = $category->parent_id;
            if ($parentId !== null && isset($nodes[$parentId])) {
                array_unshift($nodes[$parentId]['children'], $nodes[$category->id]);
            }
        }

        foreach ($categories as $category) {
            // Danh mục mà cha bị ẩn (activeOnly) cũng bị ẩn theo.
            if ($category->parent_id === null) {
                $roots[] = $nodes[$category->id];
            }
        }

        return $roots;
    }

    /**
     * @return CategoryNode
     */
    private function node(Category $category, ?string $locale): array
    {
        /** @var Mediable|null $image */
        $image = $category->media->firstWhere('role', 'image');

        return [
            'id' => $category->id,
            'brand_id' => $category->brand_id,
            'parent_id' => $category->parent_id,
            'slug' => $category->slug,
            'name' => $category->translate('name', $locale),
            'description' => $category->translate('description', $locale),
            'status' => $category->status->value,
            'position' => $category->position,
            'depth' => $category->depth,
            'image_url' => $image?->media->url(),
            'children' => [],
        ];
    }
}
