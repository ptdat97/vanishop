<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Shared\Support\StoreLocale;

final class ColorRequest extends FormRequest
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
        $color = $this->route('color');

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9-]*$/',
                Rule::unique('colors', 'code')->ignore($color instanceof Color ? $color->id : null)],
            'color_family' => ['required', Rule::enum(ColorFamily::class)],
            'hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'position' => ['required', 'integer', 'min:0'],
            'translations.'.StoreLocale::default().'.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{code: string, color_family: string, hex: string|null, position: int, translations: array<string, array{name: string}>}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'code' => $data['code'],
            'color_family' => $data['color_family'],
            'hex' => $data['hex'] ?? null,
            'position' => (int) $data['position'],
            'translations' => array_filter(array_intersect_key($data['translations'], array_flip(StoreLocale::supported())), fn (array $t): bool => filled($t['name'] ?? null)),
        ];
    }
}
