<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Catalog\Application\StaleRecord;
use Modules\Catalog\Contracts\Data\ProductDraft;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Use case sản phẩm. Mỗi thao tác là một transaction; event phát sau commit.
 */
final class ProductService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(ProductInput $input): Product
    {
        $this->guard(null, $input);

        return DB::transaction(function () use ($input): Product {
            $product = new Product;
            $this->fill($product, $input);
            $product->save();
            $this->syncRelations($product, $input);

            Hook::action('vani.product.after_save', $product->id);
            $this->audit->record('catalog.product.created', 'product', $product->id, ['product_code' => $product->product_code]);
            event(new ProductCreated($product->id, $product->product_code));

            return $product;
        });
    }

    public function update(Product $product, ProductInput $input, int $expectedLockVersion): Product
    {
        $this->guard($product->id, $input);

        return DB::transaction(function () use ($product, $input, $expectedLockVersion): Product {
            $updated = Product::query()->whereKey($product->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
            if ($updated === 0) {
                throw new StaleRecord;
            }
            $product->lock_version = $expectedLockVersion + 1;
            $product->syncOriginalAttribute('lock_version');

            $wasArchived = $product->status === ProductStatus::Archived;
            $this->fill($product, $input);
            $product->save();
            $this->syncRelations($product, $input);

            Hook::action('vani.product.after_save', $product->id);
            $this->audit->record('catalog.product.updated', 'product', $product->id, ['product_code' => $product->product_code, 'status' => $product->status->value]);

            event($product->status === ProductStatus::Archived && ! $wasArchived
                ? new ProductArchived($product->id, $product->product_code)
                : new ProductUpdated($product->id, $product->product_code));

            return $product->refresh();
        });
    }

    /**
     * Đổi trạng thái (mở bán / ngừng bán / nháp) không qua form — thao tác hàng loạt (0.3.103). Cùng hệ quả như lưu form:
     * tăng lock_version, hook after_save, audit, event ProductUpdated / ProductArchived (cache, chỉ mục tìm kiếm).
     *
     * @return bool false khi đã ở trạng thái đó
     */
    public function changeStatus(int $productId, ProductStatus $status): bool
    {
        return DB::transaction(function () use ($productId, $status): bool {
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            if ($product->status === $status) {
                return false;
            }
            $wasArchived = $product->status === ProductStatus::Archived;
            $product->status = $status;
            $product->lock_version++;
            $product->save();

            Hook::action('vani.product.after_save', $product->id);
            $this->audit->record('catalog.product.status_changed', 'product', $product->id, ['product_code' => $product->product_code, 'status' => $status->value]);
            event($status === ProductStatus::Archived && ! $wasArchived
                ? new ProductArchived($product->id, $product->product_code)
                : new ProductUpdated($product->id, $product->product_code));

            return true;
        });
    }

    /**
     * Dữ liệu hiện tại của sản phẩm dưới dạng ProductInput — để sửa một phần (nhập file, A4) qua `update` mà không làm mất bản
     * dịch, thuộc tính, khung giờ bán.
     */
    public function inputFor(Product $product): ProductInput
    {
        $product->loadMissing(['translations', 'attributeValues']);
        $types = Attribute::query()->whereIn('id', $product->attributeValues->pluck('attribute_id'))->pluck('input_type', 'id');
        $attributes = [];
        foreach ($product->attributeValues->groupBy('attribute_id') as $attributeId => $rows) {
            $attributes[(int) $attributeId] = match ($types->get($attributeId)) {
                AttributeInputType::Multiselect => $rows->pluck('attribute_value_id')->map(fn ($id): int => (int) $id)->all(),
                AttributeInputType::Select => (int) $rows->first()->attribute_value_id,
                AttributeInputType::Boolean => (bool) $rows->first()->value_bool,
                default => (string) $rows->first()->value_text,
            };
        }

        return new ProductInput(
            productCode: $product->product_code,
            slug: $product->slug,
            status: $product->status,
            translations: $product->translationsByLocale(),
            publishedFrom: $product->published_from?->toDateTimeImmutable(),
            publishedTo: $product->published_to?->toDateTimeImmutable(),
            categoryIds: $product->categories()->pluck('categories.id')->map(fn ($id): int => (int) $id)->all(),
            primaryCategoryId: $product->primary_category_id,
            attributes: $attributes,
            brandId: $product->brand_id,
            trackInventory: $product->track_inventory,
        );
    }

    /**
     * Chỉ xoá được bản nháp; sản phẩm đã bán dùng trạng thái archived để giữ lịch sử.
     */
    public function deleteDraft(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw ValidationException::withMessages(['status' => __('catalog::messages.only_draft_deletable')]);
        }

        DB::transaction(function () use ($product): void {
            foreach ($product->variants as $variant) {
                $variant->gallery()->delete();
            }
            $product->variants()->delete(); // tổ hợp tuỳ chọn xoá theo khoá ngoại (cascade)
            $product->delete();
            $this->audit->record('catalog.product.deleted', 'product', $product->id, ['product_code' => $product->product_code]);
            event(new ProductArchived($product->id, $product->product_code));
        });
    }

    /**
     * Kiểm tra dữ liệu liên quan (brand, danh mục, thuộc tính) và hook của plugin.
     */
    private function guard(?int $productId, ProductInput $input): void
    {
        try {
            new PublishWindow($input->publishedFrom, $input->publishedTo);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['published_to' => $exception->getMessage()]);
        }

        $categoryIds = array_values(array_unique($input->categoryIds));
        if ($input->brandId !== null && ! Brand::query()->whereKey($input->brandId)->exists()) {
            throw ValidationException::withMessages(['brand_id' => __('catalog::messages.brand_not_found')]);
        }

        $found = Category::query()->whereIn('id', $categoryIds)->count();
        if ($found !== count($categoryIds)) {
            throw ValidationException::withMessages(['category_ids' => __('catalog::messages.category_not_found')]);
        }
        if ($input->primaryCategoryId !== null && ! in_array($input->primaryCategoryId, $categoryIds, true)) {
            throw ValidationException::withMessages(['primary_category_id' => __('catalog::messages.primary_category_not_selected')]);
        }

        $this->validateAttributes($input->attributes);

        $issues = Hook::collect('vani.product.before_save', new ProductDraft(
            brandId: $input->brandId,
            productId: $productId,
            productCode: $input->productCode,
            slug: $input->slug,
            status: $input->status->value,
            translations: $input->translations,
            categoryIds: $categoryIds,
            attributes: $input->attributes,
        ));
        if ($issues !== []) {
            throw ValidationException::withMessages(['product' => array_map('strval', $issues)]);
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function validateAttributes(array $values): void
    {
        $attributes = Attribute::query()->with('values')->whereIn('id', array_keys($values))->get()->keyBy('id');

        foreach ($values as $attributeId => $value) {
            $attribute = $attributes->get($attributeId);
            $key = "attributes.{$attributeId}";

            if ($attribute === null) {
                throw ValidationException::withMessages([$key => __('catalog::messages.attribute_not_found')]);
            }
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $validIds = $attribute->values->pluck('id')->all();
            $ok = match ($attribute->input_type) {
                AttributeInputType::Select => is_int($value) && in_array($value, $validIds, true),
                AttributeInputType::Multiselect => is_array($value) && array_diff($value, $validIds) === [],
                AttributeInputType::Text => is_string($value) && mb_strlen($value) <= 1000,
                AttributeInputType::Boolean => is_bool($value),
            };

            if (! $ok) {
                throw ValidationException::withMessages([$key => __('catalog::messages.attribute_value_invalid')]);
            }
        }
    }

    private function fill(Product $product, ProductInput $input): void
    {
        $product->fill([
            'brand_id' => $input->brandId,
            'product_code' => $input->productCode,
            'slug' => $input->slug,
            'status' => $input->status,
            'published_from' => $input->publishedFrom,
            'published_to' => $input->publishedTo,
            'primary_category_id' => $input->primaryCategoryId,
            'search_text' => $this->searchText($input),
        ]);
        if ($input->trackInventory !== null) {
            $product->track_inventory = $input->trackInventory;
        }
    }

    private function searchText(ProductInput $input): string
    {
        $parts = [$input->productCode];
        foreach ($input->translations as $fields) {
            $parts[] = (string) ($fields['name'] ?? '');
        }

        return VietnameseText::normalize(implode(' ', $parts));
    }

    private function syncRelations(Product $product, ProductInput $input): void
    {
        $product->syncTranslations($input->translations);

        // Giữ thứ tự merchandising của danh mục vẫn được chọn; danh mục mới vào vị trí 0.
        $existing = $product->categories()->pluck('category_product.position', 'categories.id')->all();
        $sync = [];
        foreach (array_unique($input->categoryIds) as $categoryId) {
            $sync[$categoryId] = ['position' => (int) ($existing[$categoryId] ?? 0)];
        }
        $product->categories()->sync($sync);

        $product->attributeValues()->delete();
        foreach ($input->attributes as $attributeId => $value) {
            foreach ($this->attributeRows((int) $attributeId, $value) as $row) {
                $product->attributeValues()->create($row);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attributeRows(int $attributeId, mixed $value): array
    {
        return match (true) {
            $value === null, $value === '', $value === [] => [],
            is_bool($value) => [['attribute_id' => $attributeId, 'value_bool' => $value]],
            is_int($value) => [['attribute_id' => $attributeId, 'attribute_value_id' => $value]],
            is_array($value) => array_map(fn (int $id): array => ['attribute_id' => $attributeId, 'attribute_value_id' => $id], array_values(array_unique($value))),
            default => [['attribute_id' => $attributeId, 'value_text' => (string) $value]],
        };
    }
}
