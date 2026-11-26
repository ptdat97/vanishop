<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.3.43): thẻ sản phẩm cho storefront theo danh sách id product — cùng dạng một item của lưới sản phẩm
 * (`id`, `slug`, `name`, `brand`, `image_url`, `colors`, `price`, `price_hidden`, `in_stock`, `extensions`). Dùng cho khối/trang của plugin
 * cần hiện sản phẩm đã chọn sẵn (bán chạy, đã xem, lookbook…). Chỉ sản phẩm đang hiển thị; giữ thứ tự id truyền vào.
 */
#[PluginApi]
interface ProductCards
{
    public const MAX_IDS = 60;

    /**
     * @param  list<int>  $productIds  tối đa MAX_IDS (phần thừa bị bỏ)
     * @return list<array<string, mixed>>
     */
    public function forIds(array $productIds, string $locale): array;
}
