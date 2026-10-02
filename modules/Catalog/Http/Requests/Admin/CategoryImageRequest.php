<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;

final class CategoryImageRequest extends FormRequest
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
        return [
            'image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024)],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
