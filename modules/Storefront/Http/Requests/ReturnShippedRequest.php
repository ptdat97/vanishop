<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Khách báo đã gửi hàng trả (mã vận đơn tuỳ chọn) — Storefront API và Blade dùng chung (ADR-044 §2).
 */
final class ReturnShippedRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:64'],
        ];
    }
}
