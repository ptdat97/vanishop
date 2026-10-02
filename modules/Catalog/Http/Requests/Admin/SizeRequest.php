<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Catalog\Domain\SizeSystem;
use Modules\Catalog\Persistence\Models\Size;

final class SizeRequest extends FormRequest
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
        $size = $this->route('size');

        return [
            'size_system' => ['required', Rule::enum(SizeSystem::class)],
            'code' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9][A-Z0-9.\/-]*$/',
                Rule::unique('sizes', 'code')->where('size_system', $this->input('size_system'))
                    ->ignore($size instanceof Size ? $size->id : null)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }

    /**
     * @return array{size_system: string, code: string, sort_order: int}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return ['size_system' => $data['size_system'], 'code' => $data['code'], 'sort_order' => (int) $data['sort_order']];
    }
}
