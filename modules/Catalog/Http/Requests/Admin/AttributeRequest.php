<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Shared\Support\StoreLocale;

final class AttributeRequest extends FormRequest
{
    /**
     * Kiểm tra quyền trước khi validate (người chỉ có quyền xem nhận 403, không nhận lỗi form).
     */
    public function authorize(): bool
    {
        return Gate::allows('catalog.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attribute = $this->route('attribute');

        return [
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('attributes', 'code')->ignore($attribute instanceof Attribute ? $attribute->id : null)],
            'kind' => ['required', Rule::enum(AttributeKind::class)],
            'input_type' => ['required', Rule::enum(AttributeInputType::class)],
            'is_filterable' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'lock_version' => [$attribute instanceof Attribute ? 'required' : 'nullable', 'integer', 'min:0'],
            'translations.'.StoreLocale::default().'.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'values' => ['array', 'max:500'],
            'values.*.code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/', 'distinct'],
            'values.*.translations.'.StoreLocale::default().'.label' => ['required', 'string', 'max:255'],
            'values.*.translations.*.label' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{code: string, kind: string, input_type: string, is_filterable: bool, position: int, translations: array<string, array{name: string}>, values: list<array{code: string, translations: array<string, array{label: string}>}>}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'code' => $data['code'],
            'kind' => $data['kind'],
            'input_type' => $data['input_type'],
            'is_filterable' => (bool) $data['is_filterable'],
            'position' => (int) $data['position'],
            'translations' => array_filter(array_intersect_key($data['translations'], array_flip(StoreLocale::supported())), fn (array $t): bool => filled($t['name'] ?? null)),
            'values' => array_map(fn (array $value): array => [
                'code' => $value['code'],
                'translations' => array_filter(array_intersect_key($value['translations'], array_flip(StoreLocale::supported())), fn (array $t): bool => filled($t['label'] ?? null)),
            ], array_values($data['values'] ?? [])),
        ];
    }
}
