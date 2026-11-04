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
     * Thêm variant; đã có dòng cùng variant + cùng tuỳ chọn thì cộng dồn số lượng.
     *
     * @param  array<string, array<string, mixed>>  $options  tuỳ chọn theo plugin id (CartLineOption)
     */
    public function addLine(CartKey $key, int $variantId, int $quantity, array $options = []): CartView;

    /**
     * Đặt số lượng tuyệt đối; 0 = xoá dòng.
     */
    public function updateLine(CartKey $key, int $lineId, int $quantity): CartView;

    public function removeLine(CartKey $key, int $lineId): CartView;

    /**
     * Mua ngay (0.3.64): thêm như addLine rồi chỉ chọn riêng dòng đó để thanh toán, trong cùng một lần khoá giỏ.
     * Các dòng khác ở lại giỏ, bỏ chọn.
     *
     * @param  array<string, array<string, mixed>>  $options
     */
    public function buyNow(CartKey $key, int $variantId, int $quantity, array $options = []): CartView;

    /**
     * Chọn thêm các dòng để thanh toán (0.3.64). Dòng đang `unavailable` vẫn được ghi nhận nhưng không tính là đã chọn
     * cho tới khi bán lại. Id không thuộc giỏ → CartRejected::lineNotFound.
     *
     * @param  list<int>  $lineIds
     */
    public function select(CartKey $key, array $lineIds): CartView;

    /**
     * @param  list<int>  $lineIds
     */
    public function deselect(CartKey $key, array $lineIds): CartView;

    /**
     * Chỉ chọn đúng các dòng này, bỏ chọn mọi dòng khác. Mảng rỗng = bỏ chọn tất cả.
     *
     * @param  list<int>  $lineIds
     */
    public function selectOnly(CartKey $key, array $lineIds): CartView;

    /**
     * Gộp giỏ nguồn vào giỏ đích (khi khách đăng nhập): cộng dồn, kẹp theo giới hạn và số có thể bán,
     * bỏ dòng không còn bán; giỏ nguồn chuyển sang "merged".
     */
    public function merge(CartKey $source, CartKey $target): CartView;

    /**
     * Giỏ đang mở của khách hàng trên kênh hiện tại (tạo mới nếu chưa có). Giỏ của khách truy cập bằng
     * phiên đăng nhập (CurrentContext là actor customer), không cần token giỏ.
     */
    public function forCustomer(int $customerId, string $currencyCode): CartView;

    /**
     * Khi khách đăng nhập: gắn giỏ vãng lai (xác thực bằng token) vào khách. Khách đã có giỏ mở trên kênh →
     * gộp vào giỏ đó; chưa có → giỏ vãng lai thành giỏ của khách và token cũ bị vô hiệu.
     */
    public function attachToCustomer(CartKey $guestCart, int $customerId): CartView;

    /**
     * Khoá giỏ (FOR UPDATE) cho PlaceOrder và trả trạng thái hiện tại. Giỏ đã đóng → CartRejected::closed.
     * Phải gọi TRONG transaction đặt hàng: hai request đặt cùng một giỏ được xếp hàng, request sau thấy giỏ đã đóng.
     */
    public function lockForCheckout(CartKey $key): CartView;

    /**
     * Đánh dấu cả giỏ đã đặt hàng (trong cùng transaction với lockForCheckout).
     */
    public function markConverted(CartKey $key, string $orderPublicId): void;

    /**
     * Sau khi đặt đơn từ các dòng đã chọn (0.3.64, trong cùng transaction với lockForCheckout): xoá các dòng đó khỏi giỏ.
     * Không còn dòng nào → giỏ chuyển "converted" như markConverted; còn dòng → giỏ vẫn mở, dòng còn lại giữ nguyên.
     *
     * @param  list<int>  $lineIds
     */
    public function markOrdered(CartKey $key, string $orderPublicId, array $lineIds): void;
}
