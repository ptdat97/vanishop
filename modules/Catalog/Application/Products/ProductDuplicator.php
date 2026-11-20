<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Events\ProductDuplicated;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
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

    public function duplicate(Style $source, string $styleCode, string $name): Style
    {
        if (Style::query()->withoutGlobalScopes()->where('style_code', $styleCode)->exists()) {
            throw ValidationException::withMessages(['style_code' => __('validation.unique', ['attribute' => 'style code'])]);
        }
        $source->loadMissing(['options', 'optionValues', 'variants.gallery', 'variants.optionValues', 'videos']);

        return DB::transaction(function () use ($source, $styleCode, $name): Style {
            $translations = array_map(fn (array $fields): array => array_diff_key($fields, ['slug' => true]), $source->translationsByLocale());
            $translations[StoreLocale::default()]['name'] = $name;

            $style = $this->products->create(new ProductInput(
                styleCode: $styleCode,
                slug: ProductIdentifiers::uniqueSlug($name, $styleCode),
                status: StyleStatus::Draft,
                translations: $translations,
                categoryIds: $source->categories()->pluck('categories.id')->map(fn ($id): int => (int) $id)->all(),
                primaryCategoryId: $source->primary_category_id,
                brandId: $source->brand_id,
                trackInventory: $source->track_inventory,
            ));
            foreach (DB::table('style_attribute_values')->where('style_id', $source->id)->get() as $row) {
                DB::table('style_attribute_values')->insert(['style_id' => $style->id] + array_diff_key((array) $row, ['id' => true, 'style_id' => true]));
            }

            if ($source->options->isNotEmpty()) {
                $typeOf = OptionValue::query()->whereIn('id', $source->optionValues->pluck('option_value_id'))->pluck('option_type_id', 'id');
                $this->options->syncAxes($style, $source->options->map(fn ($axis): array => [
                    'option_type_id' => (int) $axis->option_type_id,
                    'value_ids' => $source->optionValues
                        ->filter(fn ($value): bool => (int) $typeOf->get($value->option_value_id) === (int) $axis->option_type_id)
                        ->pluck('option_value_id')->map(fn ($id): int => (int) $id)->values()->all(),
                ])->values()->all());
            }

            $variantIds = [];
            foreach ($source->variants->sortBy('id') as $variant) {
                $copy = $this->options->createVariant($style, array_map(fn (OptionValue $value): int => $value->id, VariantOptions::ordered($variant)), [
                    'status' => $variant->status->value, 'weight_gram' => $variant->weight_gram,
                ]);
                $media = $variant->gallery->sortBy('position')->pluck('media_id')->map(fn ($id): int => (int) $id)->all();
                if ($media !== []) {
                    $this->images->attachMedia($style, $copy, $media);
                }
                $variantIds[$variant->id] = $copy->id;
            }
            foreach ($source->videos as $position => $video) {
                $style->videos()->attach($video->id, ['position' => $position]);
            }

            $this->audit->record('catalog.product.duplicated', 'style', $style->id, ['style_code' => $styleCode, 'source_style_id' => $source->id]);
            event(new ProductDuplicated($source->id, $style->id, $variantIds));

            return $style;
        });
    }
}
