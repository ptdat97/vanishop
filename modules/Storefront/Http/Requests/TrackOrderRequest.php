<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tra cứu đơn của khách vãng lai bằng số đơn + số điện thoại — Storefront API `GET /orders/track` và Blade `POST /tra-cuu-don` (ADR-044 §2).
 */
final class TrackOrderRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
