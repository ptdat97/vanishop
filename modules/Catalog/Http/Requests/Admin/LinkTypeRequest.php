<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Shared\Support\StoreLocale;

final class LinkTypeRequest extends FormRequest
{
    use AuthorizesCatalogManage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->route('linkType');

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9][a-z0-9-]*$/',
                Rule::unique('link_types', 'code')->ignore($type instanceof LinkType ? $type->id : null)],
            'is_active' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'translations.'.StoreLocale::default().'.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{code: string, is_active: bool, position: int, translations: array<string, array{name: string}>}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'code' => $data['code'],
            'is_active' => (bool) $data['is_active'],
            'position' => (int) $data['position'],
            'translations' => array_filter(array_intersect_key($data['translations'], array_flip(StoreLocale::supported())), fn (array $t): bool => filled($t['name'] ?? null)),
        ];
    }
}
