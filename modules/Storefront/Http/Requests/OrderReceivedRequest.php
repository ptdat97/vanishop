<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Khách xác nhận đã nhận hàng (tuỳ chọn bỏ quyền đổi trả) — Storefront API và Blade dùng chung (ADR-044 §2).
 */
final class OrderReceivedRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'waive_returns' => ['sometimes', 'boolean'],
        ];
    }
}
