<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Persistence\Models\ProductCollection;

final class CollectionRequest extends FormRequest
{
    use AuthorizesCatalogManage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $collection = $this->route('collection');

        return [
            'slug' => ['required', 'string', 'max:128', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('collections', 'slug')->where('brand_id', $this->workspaceBrand()->id)->ignore($collection instanceof ProductCollection ? $collection->id : null)],
            'status' => ['required', Rule::in(['active', 'hidden'])],
            'position' => ['required', 'integer', 'min:0'],
            'translations.vi.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:5000'],
            'style_codes' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array{slug: string, status: string, position: int, translations: array<string, array{name: string, description?: string|null}>}
     */
    public function toData(): array
    {
        return [
            'slug' => (string) $this->validated('slug'),
            'status' => (string) $this->validated('status'),
            'position' => (int) $this->validated('position'),
            'translations' => array_filter(
                array_intersect_key((array) $this->input('translations', []), array_flip(['vi', 'en'])),
                fn (mixed $fields): bool => is_array($fields) && filled($fields['name'] ?? null),
            ),
        ];
    }

    /**
     * Mỗi dòng (hoặc dấu phẩy) một mã sản phẩm, theo thứ tự hiển thị.
     *
     * @return list<string>
     */
    public function styleCodes(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $this->validated('style_codes')) ?: [])));
    }
}
