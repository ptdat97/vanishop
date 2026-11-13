<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Persistence\Models\Brand;

final class BrandRequest extends FormRequest
{
    use AuthorizesCatalogManage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $brand = $this->route('brand');
        $ignore = $brand instanceof Brand ? $brand->id : null;

        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9-]*$/', Rule::unique('brands', 'code')->ignore($ignore)],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('brands', 'slug')->ignore($ignore)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'status' => ['required', Rule::in([Brand::ACTIVE, Brand::HIDDEN])],
            'position' => ['required', 'integer', 'min:0'],
            // Khoá lạc quan khi sửa: hai người sửa cùng thương hiệu → người lưu sau được báo, không ghi đè im lặng.
            'lock_version' => [$brand instanceof Brand ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array{code: string, slug: string, name: string, description: string|null, status: string, position: int}
     */
    public function toData(): array
    {
        $data = $this->validated();

        return [
            'code' => $data['code'],
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logo_media_id' => isset($data['logo_media_id']) ? (int) $data['logo_media_id'] : null,
            'status' => $data['status'],
            'position' => (int) $data['position'],
        ];
    }
}
