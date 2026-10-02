<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Category;

/**
 * Tạo danh mục gốc (path tính sau khi có id). Dùng ->childOf($parent) cho danh mục con.
 *
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'slug' => Str::slug(fake()->unique()->words(2, true)).'-'.fake()->unique()->numberBetween(1, 99999),
            'path' => '/0/',
            'depth' => 1,
            'position' => 0,
            'status' => 'active',
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Category $category): void {
            $parent = $category->parent;
            $path = $parent === null ? '/'.$category->id.'/' : $parent->path.$category->id.'/';
            $category->forceFill(['path' => $path, 'depth' => substr_count($path, '/') - 1])->saveQuietly();

            if ($category->translations()->doesntExist()) {
                $category->syncTranslations(['vi' => ['name' => Str::title(str_replace('-', ' ', $category->slug))]]);
            }
        });
    }

    public function childOf(Category $parent): self
    {
        return $this->state(['parent_id' => $parent->id]);
    }

    public function hidden(): self
    {
        return $this->state(['status' => 'hidden']);
    }
}
