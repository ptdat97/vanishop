<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Catalog\Domain\OptionDisplayType;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Shared\Support\StoreLocale;

final class OptionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('catalog.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->route('optionType');

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('option_types', 'code')->ignore($type instanceof OptionType ? $type->id : null)],
            'display_type' => ['required', Rule::enum(OptionDisplayType::class)],
            'is_filterable' => ['boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'translations.'.StoreLocale::default().'.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{code: string, display_type: string, is_filterable: bool, position: int, translations: array<string, array{name: string}>}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'code' => $data['code'], 'display_type' => $data['display_type'], 'is_filterable' => (bool) ($data['is_filterable'] ?? false), 'position' => (int) $data['position'],
            'translations' => array_filter(array_intersect_key($data['translations'], array_flip(StoreLocale::supported())), fn (array $t): bool => filled($t['name'] ?? null)),
        ];
    }
}
