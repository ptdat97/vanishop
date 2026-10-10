<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Catalog\Application\Categories\CategoryInput;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Contracts\CatalogImporter;
use Modules\Catalog\Contracts\Data\ImportedProduct;
use Modules\Catalog\Contracts\Data\ProductImport;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleColor;
use Modules\Shared\Support\StoreLocale;

/**
 * Nhập sản phẩm qua các service Admin sẵn có (ProductService, StyleColorService, VariantService, TaxonomyService,
 * CategoryService) — cùng kiểm tra, audit, event như thao tác của nhân viên.
 */
final class ProductImporter implements CatalogImporter
{
    public function __construct(
        private readonly ProductService $products,
        private readonly StyleColorService $styleColors,
        private readonly VariantService $variants,
        private readonly TaxonomyService $taxonomy,
        private readonly CategoryService $categories,
    ) {}

    public function upsertProduct(ProductImport $import): ImportedProduct
    {
        if ($import->colors === [] || $import->sizes === [] || ! isset($import->translations[StoreLocale::default()]['name'])) {
            throw new InvalidArgumentException('ProductImport cần ít nhất một màu, một size và tên tiếng Việt.');
        }

        return DB::transaction(function () use ($import): ImportedProduct {
            $brandId = $import->brand === null ? null : $this->brand($import->brand)->id;
            $categoryIds = array_map(fn (array $category): int => $this->category($category)->id, $import->categories);

            $style = Style::query()->withoutGlobalScopes()->where('style_code', $import->styleCode)->first();
            $created = $style === null;
            $style ??= $this->products->create(new ProductInput(
                styleCode: $import->styleCode,
                slug: $import->slug,
                status: $import->active ? StyleStatus::Active : StyleStatus::Draft,
                translations: $import->translations,
                categoryIds: $categoryIds,
                primaryCategoryId: $categoryIds[0] ?? null,
                brandId: $brandId,
            ));

            $imagesAdded = 0;
            foreach ($import->colors as $position => $colorData) {
                $color = $this->color($colorData, $position);
                $styleColor = StyleColor::query()->where('style_id', $style->id)->where('color_id', $color->id)->first()
                    ?? $this->styleColors->addColor($style, $color->id);
                if ($colorData['images'] !== [] && ! $styleColor->gallery()->exists()) {
                    $files = array_map(fn (string $path): UploadedFile => new UploadedFile($path, basename($path), null, null, true), array_slice($colorData['images'], 0, StyleColorService::MAX_IMAGES_PER_COLOR));
                    $this->styleColors->addImages($style, $styleColor, $files);
                    $imagesAdded += count($files);
                }
            }

            $sizeIds = array_map(fn (string $code, int $index): int => $this->size($code, $index)->id, $import->sizes, array_keys($import->sizes));
            $this->variants->generate($style->fresh() ?? $style, $sizeIds);

            return new ImportedProduct(
                $style->id, $style->slug, $created, $imagesAdded,
                DB::table('variants')->where('style_id', $style->id)->orderBy('id')->pluck('sku')->map(fn ($sku): string => (string) $sku)->all(),
            );
        });
    }

    /**
     * @param  array{slug: string, code: string, name: string}  $data
     */
    private function brand(array $data): Brand
    {
        return Brand::query()->where('slug', $data['slug'])->first()
            ?? $this->taxonomy->saveBrand(['code' => $data['code'], 'slug' => $data['slug'], 'name' => $data['name'], 'description' => null, 'status' => 'active', 'position' => (int) Brand::query()->max('position') + 1]);
    }

    /**
     * @param  array{slug: string, name: string, parent_slug?: string|null}  $data
     */
    private function category(array $data): Category
    {
        $existing = Category::query()->where('slug', $data['slug'])->first();
        if ($existing !== null) {
            return $existing;
        }
        $parentId = isset($data['parent_slug']) ? Category::query()->where('slug', $data['parent_slug'])->value('id') : null;

        return $this->categories->create(new CategoryInput($data['slug'], CategoryStatus::Active, [StoreLocale::default() => ['name' => $data['name']]], $parentId === null ? null : (int) $parentId));
    }

    /**
     * @param  array{code: string, name: string, hex: string, family?: string}  $data
     */
    private function color(array $data, int $position): Color
    {
        return Color::query()->where('code', $data['code'])->first()
            ?? $this->taxonomy->saveColor(['code' => $data['code'], 'color_family' => $data['family'] ?? 'multi', 'hex' => $data['hex'], 'position' => $position, 'translations' => [StoreLocale::default() => ['name' => $data['name']]]]);
    }

    private function size(string $code, int $index): Size
    {
        return Size::query()->where('code', $code)->first()
            ?? $this->taxonomy->saveSize(['size_system' => 'alpha', 'code' => $code, 'sort_order' => $index]);
    }
}
