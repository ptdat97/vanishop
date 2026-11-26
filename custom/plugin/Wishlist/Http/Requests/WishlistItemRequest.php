<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Thêm một sản phẩm yêu thích — Storefront API `POST x/vani-wishlist/items` (nút tim của wishlist.js và app).
 */
final class WishlistItemRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
