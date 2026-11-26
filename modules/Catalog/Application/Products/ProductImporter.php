<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Catalog\Application\Categories\CategoryInput;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Search\ProductListingProjector;
use Modules\Catalog\Application\Taxonomy\OptionTypeService;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Contracts\CatalogImporter;
use Modules\Catalog\Contracts\Data\ImportedProduct;
use Modules\Catalog\Contracts\Data\ProductImport;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Shared\Support\StoreLocale;

/**
 * Nhập sản phẩm qua các service Admin sẵn có (ProductService, ProductOptions, VariantImages, OptionTypeService, TaxonomyService,
 * CategoryService) — cùng kiểm tra, audit, event như thao tác của nhân viên.
 */
final class ProductImporter implements CatalogImporter
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductOptions $options,
        private readonly VariantImages $images,
        private readonly OptionTypeService $optionTypes,
        private readonly TaxonomyService $taxonomy,
        private readonly CategoryService $categories,
        private readonly ProductListingProjector $listing,
    ) {}

    public function upsertProduct(ProductImport $import): ImportedProduct
    {
        if ($import->colors === [] || $import->sizes === [] || ! isset($import->translations[StoreLocale::default()]['name'])) {
            throw new InvalidArgumentException('ProductImport cần ít nhất một màu, một size và tên tiếng Việt.');
        }

        return DB::transaction(fn () => $this->listing->deferring(function () use ($import): ImportedProduct {
            $brandId = $import->brand === null ? null : $this->brand($import->brand)->id;
            $categoryIds = array_map(fn (array $category): int => $this->category($category)->id, $import->categories);

            $product = Product::query()->withoutGlobalScopes()->where('product_code', $import->productCode)->first();
            $created = $product === null;
            $product ??= $this->products->create(new ProductInput(
                productCode: $import->productCode,
                slug: $import->slug,
                status: $import->active ? ProductStatus::Active : ProductStatus::Draft,
                translations: $import->translations,
                categoryIds: $categoryIds,
                primaryCategoryId: $categoryIds[0] ?? null,
                brandId: $brandId,
            ));

            // ADR-045: hai trục Màu × Size (loại có sẵn); giá trị chưa có thì tạo; giữ giá trị/SKU sản phẩm đã có.
            $colorType = OptionType::query()->where('code', OptionType::COLOR)->firstOrFail();
            $sizeType = OptionType::query()->where('code', OptionType::SIZE)->firstOrFail();
            $colorIds = array_map(fn (array $data, int $position): int => $this->value($colorType, $data['code'], $position, [StoreLocale::default() => $data['name']], $data['hex'], $data['family'] ?? 'multi')->id, $import->colors, array_keys($import->colors));
            $sizeIds = array_map(fn (string $code, int $position): int => $this->value($sizeType, $code, $position, [], null, null, 'alpha')->id, $import->sizes, array_keys($import->sizes));
            $current = $product->optionValues()->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all();
            $existingOf = fn (OptionType $type): array => OptionValue::query()->whereIn('id', $current)->where('option_type_id', $type->id)->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $this->options->syncAxes($product, [
                ['option_type_id' => $colorType->id, 'value_ids' => array_values(array_unique([...$existingOf($colorType), ...$colorIds]))],
                ['option_type_id' => $sizeType->id, 'value_ids' => array_values(array_unique([...$existingOf($sizeType), ...$sizeIds]))],
            ]);
            $keys = Variant::query()->where('product_id', $product->id)->pluck('option_key')->all();
            foreach ($colorIds as $colorId) {
                foreach ($sizeIds as $sizeId) {
                    if (! in_array(Variant::keyFor([$colorId, $sizeId]), $keys, true)) {
                        $this->options->createVariant($product, [$colorId, $sizeId]);
                    }
                }
            }

            // Ảnh theo SKU: tải ảnh của màu cho SKU đầu tiên của màu rồi áp cho mọi SKU cùng màu.
            $imagesAdded = 0;
            foreach ($import->colors as $index => $colorData) {
                if ($colorData['images'] === []) {
                    continue;
                }
                $first = Variant::query()->where('product_id', $product->id)
                    ->whereIn('id', DB::table('variant_option_values')->where('option_value_id', $colorIds[$index])->select('variant_id'))->orderBy('id')->first();
                if ($first === null) {
                    continue;
                }
                if ($import->replaceImages) {
                    $first->gallery()->delete();
                }
                if (! $first->gallery()->exists()) {
                    $files = array_map(fn (string $path): UploadedFile => new UploadedFile($path, basename($path), null, null, true), array_slice($colorData['images'], 0, VariantImages::MAX_IMAGES));
                    $this->images->addFiles($product, $first, $files);
                    $this->images->copyToSiblings($product, $first, $colorType->id);
                    $imagesAdded += count($files);
                }
            }

            $this->listing->project($product->id);

            return new ImportedProduct(
                $product->id, $product->slug, $created, $imagesAdded,
                DB::table('variants')->where('product_id', $product->id)->orderBy('id')->pluck('sku')->map(fn ($sku): string => (string) $sku)->all(),
            );
        }));
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
     * Giá trị tuỳ chọn theo mã (chưa có thì tạo).
     *
     * @param  array<string, string>  $names
     */
    private function value(OptionType $type, string $code, int $position, array $names, ?string $hex, ?string $family, ?string $group = null): OptionValue
    {
        return OptionValue::query()->where('option_type_id', $type->id)->where('code', $code)->first()
            ?? $this->optionTypes->saveValue($type, [
                'code' => $code, 'group' => $group, 'hex' => $hex, 'family' => $family, 'position' => $position,
                'translations' => array_map(fn (string $name): array => ['name' => $name], array_filter($names)),
            ]);
    }
}
