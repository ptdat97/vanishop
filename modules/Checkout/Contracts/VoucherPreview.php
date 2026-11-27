<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Data\VoucherPreviewResult;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.109): tính thử từng mã trên giỏ — mỗi mã một mình, cùng khuyến mãi tự động, như khi khách chỉ nhập
 * mã đó (ví voucher, "Chọn mã tốt nhất"). Không ghi gì, không đổi mã đang gắn với giỏ. Dùng các dòng khách đang chọn để
 * thanh toán và khách đang đăng nhập (nếu có).
 */
#[PluginApi]
interface VoucherPreview
{
    public const MAX_CODES = 50;

    /**
     * @param  list<string>  $codes  tối đa MAX_CODES mã (phần dư bị bỏ)
     * @return list<VoucherPreviewResult> theo thứ tự `$codes` (mã rỗng/trùng bị bỏ)
     */
    public function forCart(CartKey $cart, array $codes): array;
}
