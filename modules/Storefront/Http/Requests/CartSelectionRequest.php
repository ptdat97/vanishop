<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Chọn dòng để thanh toán — Storefront API `PUT /carts/{id}/selection` và Blade `POST /gio-hang/chon` (ADR-044 §2). Dạng
 * chuẩn của API: `mode` (`only` mặc định / `select` / `deselect`) + `line_ids`. Form Blade gửi `lines[]` (các ô đã đánh dấu,
 * kể cả khi JS tắt) = `mode=only`, và `checkout=1` cho nút "Thanh toán".
 */
final class CartSelectionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('line_ids') && ! $this->has('mode')) {
            $this->merge(['mode' => 'only', 'line_ids' => (array) $this->input('lines', [])]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['sometimes', 'string', 'in:only,select,deselect'],
            'line_ids' => ['present', 'array', 'max:100'],
            'line_ids.*' => ['integer', 'min:1'],
            'checkout' => ['nullable', 'boolean'],
        ];
    }

    public function mode(): string
    {
        return (string) ($this->validated('mode') ?? 'only');
    }

    /**
     * @return list<int>
     */
    public function lineIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('line_ids')));
    }
}
