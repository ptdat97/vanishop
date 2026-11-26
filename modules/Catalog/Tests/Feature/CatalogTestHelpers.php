<?php

declare(strict_types=1);

namespace Modules\Catalog\Tests\Feature;

use Closure;
use Modules\Catalog\Application\Products\ProductInput;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Application\Search\ProductListingProjector;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

final class CatalogTestHelpers
{
    /**
     * Tạo dữ liệu với phạm vi hệ thống. Sau đó dựng lại read model trang danh sách của mọi sản phẩm (db-refactor §4.3): test
     * hay sửa thẳng quan hệ/model (gắn danh mục, đổi nhóm màu) — như thể thay đổi đã đi qua use case của Catalog.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function seed(Closure $callback): mixed
    {
        $result = app(CurrentContext::class)->runAs(ContextScope::system('test seed'), $callback);
        self::project();

        return $result;
    }

    /** Dựng lại read model trang danh sách của mọi sản phẩm (sau khi test ghi thẳng DB). */
    public static function project(): void
    {
        app(ProductListingProjector::class)->project(Product::query()->withoutGlobalScopes()->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    /**
     * Tạo sản phẩm qua ProductService (đầy đủ search_text, hook, event). $brandId = thương hiệu (thuộc tính catalog).
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function product(?int $brandId = null, array $overrides = []): Product
    {
        $input = new ProductInput(
            productCode: $overrides['product_code'] ?? strtoupper(fake()->unique()->bothify('ST##??')),
            slug: $overrides['slug'] ?? fake()->unique()->slug(3),
            status: ProductStatus::from($overrides['status'] ?? 'active'),
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
     * Thêm giá trị tuỳ chọn vào trục của sản phẩm (ADR-045): giữ trục và giá trị đang có, loại mới thêm thành trục cuối.
     */
    public static function optionValues(Product $product, OptionValue ...$values): void
    {
        self::seed(function () use ($product, $values): void {
            $axes = [];
            foreach ($product->options()->get() as $axis) {
                $axes[$axis->option_type_id] = $product->optionValues()->whereHas('value', fn ($query) => $query->where('option_type_id', $axis->option_type_id))
                    ->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all();
            }
            foreach ($values as $value) {
                $axes[$value->option_type_id][] = $value->id;
            }
            $payload = [];
            foreach ($axes as $typeId => $valueIds) {
                $payload[] = ['option_type_id' => (int) $typeId, 'value_ids' => array_values(array_unique($valueIds))];
            }
            app(ProductOptions::class)->syncAxes($product, $payload);
        });
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function staff(array $permissions = ['admin.access', 'catalog.view', 'catalog.manage']): StaffUser
    {
        return StaffUser::factory()->withPermissions($permissions)->create();
    }
}
