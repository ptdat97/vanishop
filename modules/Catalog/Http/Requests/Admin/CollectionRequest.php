<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Shared\Support\StoreLocale;

final class CollectionRequest extends FormRequest
{
    use AuthorizesCatalogManage;
    use ValidatesLocalizedSlugs;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $collection = $this->route('collection');

        return [
            'slug' => ['required', 'string', 'max:128', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('collections', 'slug')->ignore($collection instanceof ProductCollection ? $collection->id : null)],
            'status' => ['required', Rule::in(['active', 'hidden'])],
            'position' => ['required', 'integer', 'min:0'],
            'image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            // Khoá lạc quan khi sửa: hai người sửa cùng bộ sưu tập → người lưu sau được báo, không ghi đè im lặng.
            'lock_version' => [$collection instanceof ProductCollection ? 'required' : 'nullable', 'integer', 'min:0'],
            'translations.'.StoreLocale::default().'.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:5000'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:512'],
            'style_codes' => ['nullable', 'string', 'max:20000'],
            ...$this->localizedSlugRules('collections', 'collection_translations', 'collection_id', $collection instanceof ProductCollection ? $collection->id : null),
        ];
    }

    /**
     * @return array{slug: string, status: string, position: int, image_media_id: int|null, translations: array<string, array{name: string, description?: string|null, meta_title?: string|null, meta_description?: string|null}>}
     */
    public function toData(): array
    {
        return [
            'slug' => (string) $this->validated('slug'),
            'status' => (string) $this->validated('status'),
            'position' => (int) $this->validated('position'),
            'image_media_id' => $this->validated('image_media_id') === null ? null : (int) $this->validated('image_media_id'),
            'translations' => $this->normalizeLocalizedSlugs(array_filter(
                array_intersect_key((array) $this->validated('translations', []), array_flip(StoreLocale::supported())),
                fn (mixed $fields): bool => is_array($fields) && filled($fields['name'] ?? null),
            )),
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
