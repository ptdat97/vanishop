<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Thêm vào giỏ — một hợp đồng đầu vào cho Storefront API (`POST /carts/{id}/lines`) và Blade (`POST /gio-hang`)
 * (ADR-044 §2). Không gửi số lượng = 1. `options` là tuỳ chọn dòng của plugin (CartLineOption), chuẩn hoá ở Cart.
 */
final class CartLineRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'buy_now' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['variant_id.required' => __('storefront::messages.choose_variant')];
    }

    public function variantId(): int
    {
        return (int) $this->validated('variant_id');
    }

    public function quantity(): int
    {
        return (int) ($this->validated('quantity') ?? 1);
    }

    /**
     * @return array<string, array<string, scalar|null>>
     */
    public function lineOptions(): array
    {
        return LineOptions::from($this->input('options'));
    }
}
