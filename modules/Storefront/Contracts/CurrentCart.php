<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Cart\Contracts\Data\CartKey;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.111): giỏ của người đang mua trong request web của storefront — khách vãng lai (khoá giỏ giữ trong
 * phiên) hoặc giỏ của khách đăng nhập. Plugin dùng cho slot/trang web cần tính trên giỏ (vd. ví voucher của
 * `vani.coupon-code` gọi `Checkout\Contracts\VoucherPreview`). Storefront API (app/headless) vẫn định danh giỏ bằng
 * `carts/{cart}` + `X-Vani-Cart-Token`.
 */
#[PluginApi]
interface CurrentCart
{
    /** Giỏ đang dùng được; chưa có giỏ hoặc giỏ đã đặt hàng/hết hạn → null (không tạo giỏ mới). */
    public function currentKey(): ?CartKey;
}
