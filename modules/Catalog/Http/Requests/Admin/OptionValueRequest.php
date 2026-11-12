<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Shared\Support\StoreLocale;

final class OptionValueRequest extends FormRequest
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
        $value = $this->route('value');
        $typeId = $type instanceof OptionType ? $type->id : 0;

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9.\/-]*$/',
                Rule::unique('option_values', 'code')->where('option_type_id', $typeId)->where('group', $this->input('group'))->ignore($value instanceof OptionValue ? $value->id : null)],
            'group' => ['nullable', 'string', 'max:16'],
            'hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'family' => ['nullable', Rule::enum(ColorFamily::class)],
            'position' => ['required', 'integer', 'min:0'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{code: string, group: string|null, hex: string|null, family: string|null, position: int, translations: array<string, array{name: string}>}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'code' => $data['code'], 'group' => ($data['group'] ?? '') === '' ? null : $data['group'], 'hex' => $data['hex'] ?? null,
            'family' => $data['family'] ?? null, 'position' => (int) $data['position'],
            'translations' => array_filter(array_intersect_key((array) ($data['translations'] ?? []), array_flip(StoreLocale::supported())), fn (array $t): bool => filled($t['name'] ?? null)),
        ];
    }
}
