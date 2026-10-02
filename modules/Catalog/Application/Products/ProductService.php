<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Catalog\Application\StaleRecord;
use Modules\Catalog\Contracts\Data\ProductDraft;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Extension\Facades\Hook;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Domain\Text\VietnameseText;

/**
 * Use case sản phẩm (style). Mỗi thao tác là một transaction; event phát sau commit.
 */
final class ProductService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(ProductInput $input): Style
    {
        $this->guard(null, $input);

        return DB::transaction(function () use ($input): Style {
            $style = new Style;
            $this->fill($style, $input);
            $style->save();
            $this->syncRelations($style, $input);

            Hook::action('vani.product.after_save', $style->id);
            $this->audit->record('catalog.product.created', 'style', $style->id, ['style_code' => $style->style_code]);
            event(new ProductCreated($style->id, $style->style_code));

            return $style;
        });
    }

    public function update(Style $style, ProductInput $input, int $expectedLockVersion): Style
    {
        $this->guard($style->id, $input);

        return DB::transaction(function () use ($style, $input, $expectedLockVersion): Style {
            $updated = Style::query()->whereKey($style->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
            if ($updated === 0) {
                throw new StaleRecord;
            }
            $style->lock_version = $expectedLockVersion + 1;
            $style->syncOriginalAttribute('lock_version');

            $wasArchived = $style->status === StyleStatus::Archived;
            $this->fill($style, $input);
            $style->save();
            $this->syncRelations($style, $input);

            Hook::action('vani.product.after_save', $style->id);
            $this->audit->record('catalog.product.updated', 'style', $style->id, ['style_code' => $style->style_code, 'status' => $style->status->value]);

            event($style->status === StyleStatus::Archived && ! $wasArchived
                ? new ProductArchived($style->id, $style->style_code)
                : new ProductUpdated($style->id, $style->style_code));

            return $style->refresh();
        });
    }

    /**
     * Chỉ xoá được bản nháp; sản phẩm đã bán dùng trạng thái archived để giữ lịch sử.
     */
    public function deleteDraft(Style $style): void
    {
        if ($style->status !== StyleStatus::Draft) {
            throw ValidationException::withMessages(['status' => __('catalog::messages.only_draft_deletable')]);
        }

        DB::transaction(function () use ($style): void {
            $style->variants()->delete();
            foreach ($style->colors as $color) {
                $color->gallery()->delete();
            }
            $style->delete();
            $this->audit->record('catalog.product.deleted', 'style', $style->id, ['style_code' => $style->style_code]);
            event(new ProductArchived($style->id, $style->style_code));
        });
    }

    /**
     * Kiểm tra dữ liệu liên quan (brand, danh mục, thuộc tính) và hook của plugin.
     */
    private function guard(?int $styleId, ProductInput $input): void
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
            styleId: $styleId,
            styleCode: $input->styleCode,
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

    private function fill(Style $style, ProductInput $input): void
    {
        $style->fill([
            'brand_id' => $input->brandId,
            'style_code' => $input->styleCode,
            'slug' => $input->slug,
            'status' => $input->status,
            'published_from' => $input->publishedFrom,
            'published_to' => $input->publishedTo,
            'primary_category_id' => $input->primaryCategoryId,
            'search_text' => $this->searchText($input),
        ]);
    }

    private function searchText(ProductInput $input): string
    {
        $parts = [$input->styleCode];
        foreach ($input->translations as $fields) {
            $parts[] = (string) ($fields['name'] ?? '');
        }

        return VietnameseText::normalize(implode(' ', $parts));
    }

    private function syncRelations(Style $style, ProductInput $input): void
    {
        $style->syncTranslations($input->translations);

        // Giữ thứ tự merchandising của danh mục vẫn được chọn; danh mục mới vào vị trí 0.
        $existing = $style->categories()->pluck('category_style.position', 'categories.id')->all();
        $sync = [];
        foreach (array_unique($input->categoryIds) as $categoryId) {
            $sync[$categoryId] = ['position' => (int) ($existing[$categoryId] ?? 0)];
        }
        $style->categories()->sync($sync);

        $style->attributeValues()->delete();
        foreach ($input->attributes as $attributeId => $value) {
            foreach ($this->attributeRows((int) $attributeId, $value) as $row) {
                $style->attributeValues()->create($row);
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
