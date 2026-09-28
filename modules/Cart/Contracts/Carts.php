<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts;

use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Cart\Contracts\Data\NewCart;

/**
 * Service contract giỏ hàng. Kênh lấy từ CurrentContext; giỏ của kênh khác coi như không tồn tại.
 * Giỏ KHÔNG giữ hàng — chỉ kiểm tra số có thể bán; giữ hàng xảy ra trong PlaceOrder.
 *
 * Mọi phương thức ném CartRejected cho lỗi nghiệp vụ.
 */
interface Carts
{
    public function create(string $currencyCode): NewCart;

    public function view(CartKey $key): CartView;

    /**
     * Thêm variant; đã có trong giỏ thì cộng dồn số lượng.
     */
    public function addLine(CartKey $key, int $variantId, int $quantity): CartView;

    /**
     * Đặt số lượng tuyệt đối; 0 = xoá dòng.
     */
    public function updateLine(CartKey $key, int $lineId, int $quantity): CartView;

    public function removeLine(CartKey $key, int $lineId): CartView;

    /**
     * Gộp giỏ nguồn vào giỏ đích (khi khách đăng nhập): cộng dồn, kẹp theo giới hạn và số có thể bán,
     * bỏ dòng không còn bán; giỏ nguồn chuyển sang "merged".
     */
    public function merge(CartKey $source, CartKey $target): CartView;
}
