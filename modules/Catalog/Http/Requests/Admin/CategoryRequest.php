<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Categories\CategoryInput;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Identity\Contracts\Data\ScopeRef;

final class CategoryRequest extends FormRequest
{
    /**
     * Kiểm tra quyền trước khi validate (người chỉ có quyền xem nhận 403, không nhận lỗi form).
     */
    public function authorize(): bool
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');

        return Gate::allows('catalog.manage', [ScopeRef::brand($brand->id)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');
        $category = $this->route('category');

        return [
            'slug' => ['required', 'string', 'max:128', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->where('brand_id', $brand->id)->ignore($category instanceof Category ? $category->id : null)],
            'parent_id' => ['nullable', 'integer'],
            'status' => ['required', Rule::enum(CategoryStatus::class)],
            'position' => ['required', 'integer', 'min:0', 'max:100000'],
            'lock_version' => [$category instanceof Category ? 'required' : 'nullable', 'integer', 'min:0'],
            'translations' => ['required', 'array'],
            'translations.vi.name' => ['required', 'string', 'max:255'],
            'translations.*' => ['array'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:20000'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function toInput(): CategoryInput
    {
        $translations = array_filter(
            array_intersect_key((array) $this->validated('translations'), array_flip(['vi', 'en'])),
            fn (array $fields): bool => filled($fields['name'] ?? null),
        );

        return new CategoryInput(
            slug: (string) $this->validated('slug'),
            status: CategoryStatus::from((string) $this->validated('status')),
            translations: $translations,
            parentId: $this->validated('parent_id') === null ? null : (int) $this->validated('parent_id'),
            position: (int) $this->validated('position'),
        );
    }
}
