<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Application\Products\ProductInput;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Persistence\Models\Style;

final class ProductRequest extends FormRequest
{
    use AuthorizesCatalogManage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $style = $this->route('product');
        $ignore = $style instanceof Style ? $style->id : null;

        return [
            'style_code' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/', Rule::unique('styles', 'style_code')->ignore($ignore)],
            'slug' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('styles', 'slug')->ignore($ignore)],
            'status' => ['required', Rule::enum(StyleStatus::class)],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'published_from' => ['nullable', 'date'],
            'published_to' => ['nullable', 'date'],
            'lock_version' => [$style instanceof Style ? 'required' : 'nullable', 'integer', 'min:0'],
            'category_ids' => ['array', 'max:20'],
            'category_ids.*' => ['integer', 'distinct'],
            'primary_category_id' => ['nullable', 'integer'],
            'translations.vi.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:20000'],
            'translations.*.care_instructions' => ['nullable', 'string', 'max:5000'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:512'],
            'attributes' => ['array'],
        ];
    }

    public function toInput(): ProductInput
    {
        $translations = array_filter(
            array_intersect_key((array) $this->input('translations', []), array_flip(['vi', 'en'])),
            fn (mixed $fields): bool => is_array($fields) && filled($fields['name'] ?? null),
        );

        return new ProductInput(
            styleCode: (string) $this->validated('style_code'),
            slug: (string) $this->validated('slug'),
            status: StyleStatus::from((string) $this->validated('status')),
            translations: $translations,
            publishedFrom: $this->dateTimeInput('published_from'),
            publishedTo: $this->dateTimeInput('published_to'),
            categoryIds: array_map('intval', (array) $this->validated('category_ids', [])),
            primaryCategoryId: $this->validated('primary_category_id') === null ? null : (int) $this->validated('primary_category_id'),
            attributes: $this->attributeValues(),
            brandId: $this->validated('brand_id') === null ? null : (int) $this->validated('brand_id'),
        );
    }

    /**
     * Chuẩn hoá giá trị thuộc tính từ form: số → int, mảng → list<int>, "true"/"false" → bool.
     *
     * @return array<int, mixed>
     */
    private function attributeValues(): array
    {
        $values = [];
        foreach ((array) $this->input('attributes', []) as $attributeId => $value) {
            $values[(int) $attributeId] = match (true) {
                is_array($value) => array_map('intval', array_values($value)),
                is_bool($value) => $value,
                is_int($value) => $value,
                is_string($value) && ctype_digit($value) => (int) $value,
                $value === null => null,
                default => (string) $value,
            };
        }

        return $values;
    }

    private function dateTimeInput(string $key): ?DateTimeImmutable
    {
        $value = $this->validated($key);

        return $value === null ? null : new DateTimeImmutable((string) $value);
    }
}
