<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Đặt số lượng một dòng giỏ (0 = xoá) — Storefront API `PATCH /carts/{id}/lines/{line}` và Blade `POST /gio-hang/{line}`
 * (ADR-044 §2).
 */
final class CartQuantityRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:0', 'max:1000']];
    }
}
