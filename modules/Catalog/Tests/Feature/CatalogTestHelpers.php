<?php

declare(strict_types=1);

namespace Modules\Catalog\Tests\Feature;

use Closure;
use Modules\Catalog\Application\Products\ProductInput;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class CatalogTestHelpers
{
    /**
     * Tạo dữ liệu với phạm vi hệ thống.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function seed(Closure $callback): mixed
    {
        return app(CurrentContext::class)->runAs(ContextScope::system('test seed'), $callback);
    }

    /**
     * Tạo sản phẩm qua ProductService (đầy đủ search_text, hook, event). $brandId = thương hiệu (thuộc tính catalog).
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function product(?int $brandId = null, array $overrides = []): Style
    {
        $input = new ProductInput(
            styleCode: $overrides['style_code'] ?? strtoupper(fake()->unique()->bothify('ST##??')),
            slug: $overrides['slug'] ?? fake()->unique()->slug(3),
            status: StyleStatus::from($overrides['status'] ?? 'active'),
            translations: $overrides['translations'] ?? ['vi' => ['name' => $overrides['name'] ?? 'Sản phẩm '.fake()->word()]],
            publishedFrom: $overrides['published_from'] ?? null,
            publishedTo: $overrides['published_to'] ?? null,
            categoryIds: $overrides['category_ids'] ?? [],
            primaryCategoryId: $overrides['primary_category_id'] ?? null,
            attributes: $overrides['attributes'] ?? [],
            brandId: $brandId,
        );

        return self::seed(fn () => app(ProductService::class)->create($input));
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function staff(array $permissions = ['admin.access', 'catalog.view', 'catalog.manage']): StaffUser
    {
        return StaffUser::factory()->withPermissions($permissions)->create();
    }
}
