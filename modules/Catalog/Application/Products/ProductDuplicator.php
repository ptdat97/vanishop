<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Events\ProductDuplicated;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;

/**
 * Nhân bản sản phẩm thành **bản nháp** (stabilization A4) — ra mẫu tương tự nhanh: chép bản dịch (tên mới cho ngôn ngữ mặc
 * định, bỏ slug theo ngôn ngữ), thương hiệu, danh mục, thuộc tính, trục tuỳ chọn, SKU (mã sinh theo mã sản phẩm mới, barcode
 * để trống, giữ trạng thái và khối lượng), ảnh và video (dùng lại media trong Thư viện). Không chép tồn kho, bộ sưu tập, liên
 * kết sản phẩm, lượt xem. Giá niêm yết do Pricing chép qua event `ProductDuplicated`.
 */
final class ProductDuplicator
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductOptions $options,
        private readonly VariantImages $images,
        private readonly AuditLogger $audit,
    ) {}

    public function duplicate(Product $source, string $productCode, string $name): Product
    {
        if (Product::query()->withoutGlobalScopes()->where('product_code', $productCode)->exists()) {
            throw ValidationException::withMessages(['product_code' => __('validation.unique', ['attribute' => 'product code'])]);
        }
        $source->loadMissing(['options', 'optionValues', 'variants.gallery', 'variants.optionValues', 'videos']);

        return DB::transaction(function () use ($source, $productCode, $name): Product {
            $translations = array_map(fn (array $fields): array => array_diff_key($fields, ['slug' => true]), $source->translationsByLocale());
            $translations[StoreLocale::default()]['name'] = $name;

            $product = $this->products->create(new ProductInput(
                productCode: $productCode,
                slug: ProductIdentifiers::uniqueSlug($name, $productCode),
                status: ProductStatus::Draft,
                translations: $translations,
                categoryIds: $source->categories()->pluck('categories.id')->map(fn ($id): int => (int) $id)->all(),
                primaryCategoryId: $source->primary_category_id,
                brandId: $source->brand_id,
                trackInventory: $source->track_inventory,
            ));
            foreach (DB::table('product_attribute_values')->where('product_id', $source->id)->get() as $row) {
                DB::table('product_attribute_values')->insert(['product_id' => $product->id] + array_diff_key((array) $row, ['id' => true, 'product_id' => true]));
            }

            if ($source->options->isNotEmpty()) {
                $typeOf = OptionValue::query()->whereIn('id', $source->optionValues->pluck('option_value_id'))->pluck('option_type_id', 'id');
                $this->options->syncAxes($product, $source->options->map(fn ($axis): array => [
                    'option_type_id' => (int) $axis->option_type_id,
                    'value_ids' => $source->optionValues
                        ->filter(fn ($value): bool => (int) $typeOf->get($value->option_value_id) === (int) $axis->option_type_id)
                        ->pluck('option_value_id')->map(fn ($id): int => (int) $id)->values()->all(),
                ])->values()->all());
            }

            $variantIds = [];
            foreach ($source->variants->sortBy('id') as $variant) {
                $copy = $this->options->createVariant($product, array_map(fn (OptionValue $value): int => $value->id, VariantOptions::ordered($variant)), [
                    'status' => $variant->status->value, 'weight_gram' => $variant->weight_gram,
                ]);
                $media = $variant->gallery->sortBy('position')->pluck('media_id')->map(fn ($id): int => (int) $id)->all();
                if ($media !== []) {
                    $this->images->attachMedia($product, $copy, $media);
                }
                $variantIds[$variant->id] = $copy->id;
            }
            foreach ($source->videos as $position => $video) {
                $product->videos()->attach($video->id, ['position' => $position]);
            }

            $this->audit->record('catalog.product.duplicated', 'product', $product->id, ['product_code' => $productCode, 'source_product_id' => $source->id]);
            event(new ProductDuplicated($source->id, $product->id, $variantIds));

            return $product;
        });
    }
}
