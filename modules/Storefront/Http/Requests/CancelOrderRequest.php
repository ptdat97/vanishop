<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Khách tự huỷ đơn (lý do bắt buộc) — Storefront API `POST /me/orders/{order}/cancel`, `POST /orders/{order}/cancel` và Blade `POST /tai-khoan/don-hang/{order}/huy` (ADR-044 §2).
 */
final class CancelOrderRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
