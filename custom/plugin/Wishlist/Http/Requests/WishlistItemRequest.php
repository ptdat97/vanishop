<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Thêm/bỏ một sản phẩm yêu thích — nút tim (form Blade khi tắt JS) và Storefront API `POST x/vani-wishlist/items` dùng
 * chung (ADR-044 §2).
 */
final class WishlistItemRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'style_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
