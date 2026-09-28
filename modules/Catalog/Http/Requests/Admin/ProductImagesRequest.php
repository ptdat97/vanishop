<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class ProductImagesRequest extends FormRequest
{
    use AuthorizesCatalogManage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024)],
        ];
    }
}
