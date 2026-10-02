<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Categories;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\StaleRecord;
use Modules\Catalog\Domain\Category\CategoryCycle;
use Modules\Catalog\Domain\Category\CategoryHasChildren;
use Modules\Catalog\Domain\Category\CategoryPath;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Use case danh mục. Mỗi thao tác là một transaction; path/depth của cả cây con được giữ đúng.
 */
final class CategoryService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(CategoryInput $input): Category
    {
        return DB::transaction(function () use ($input): Category {
            $parent = $this->parentOrNull($input->parentId);

            $category = Category::query()->create([
                'parent_id' => $parent?->id,
                'slug' => $input->slug,
                'status' => $input->status,
                'position' => $input->position,
                'path' => '/0/',
                'depth' => 1,
            ]);

            $path = $parent === null ? CategoryPath::root($category->id) : $parent->categoryPath()->child($category->id);
            $category->forceFill(['path' => $path->toString(), 'depth' => $path->depth()])->save();
            $category->syncTranslations($input->translations);

            $this->audit->record('catalog.category.created', 'category', $category->id, ['slug' => $category->slug]);

            return $category;
        });
    }

    /**
     * Sửa nội dung; nếu đổi danh mục cha thì di chuyển cả cây con.
     */
    public function update(Category $category, CategoryInput $input, int $expectedLockVersion): Category
    {
        return DB::transaction(function () use ($category, $input, $expectedLockVersion): Category {
            $this->bumpLockVersion($category, $expectedLockVersion);

            if ($input->parentId !== $category->parent_id) {
                $this->moveSubtree($category, $input->parentId);
            }

            $category->forceFill([
                'slug' => $input->slug,
                'status' => $input->status,
                'position' => $input->position,
            ])->save();
            $category->syncTranslations($input->translations);

            $this->audit->record('catalog.category.updated', 'category', $category->id, ['slug' => $category->slug, 'parent_id' => $category->parent_id]);

            return $category->refresh();
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            if (Category::query()->where('parent_id', $category->id)->lockForUpdate()->exists()) {
                throw new CategoryHasChildren;
            }

            $category->media()->delete();
            $category->delete();

            $this->audit->record('catalog.category.deleted', 'category', $category->id, ['slug' => $category->slug]);
        });
    }

    private function moveSubtree(Category $category, ?int $newParentId): void
    {
        $from = $category->categoryPath();
        $parent = $this->parentOrNull($newParentId);

        if ($parent !== null && $parent->categoryPath()->isWithin($from)) {
            throw new CategoryCycle;
        }

        $to = $parent === null ? CategoryPath::root($category->id) : $parent->categoryPath()->child($category->id);

        $subtree = Category::query()
            ->where('path', 'like', $from->toString().'%')
            ->lockForUpdate()
            ->get();

        foreach ($subtree as $node) {
            $newPath = $node->categoryPath()->rebase($from, $to);
            $node->forceFill(['path' => $newPath->toString(), 'depth' => $newPath->depth()])->save();
        }

        $category->forceFill(['parent_id' => $parent?->id, 'path' => $to->toString(), 'depth' => $to->depth()]);
    }

    private function parentOrNull(?int $parentId): ?Category
    {
        if ($parentId === null) {
            return null;
        }

        $parent = Category::query()->lockForUpdate()->find($parentId);

        if ($parent === null) {
            throw ValidationException::withMessages(['parent_id' => __('catalog::messages.parent_not_found')]);
        }

        return $parent;
    }

    private function bumpLockVersion(Category $category, int $expected): void
    {
        $updated = Category::query()
            ->whereKey($category->id)
            ->where('lock_version', $expected)
            ->increment('lock_version');

        if ($updated === 0) {
            throw new StaleRecord;
        }

        $category->lock_version = $expected + 1;
        $category->syncOriginalAttribute('lock_version');
    }
}
