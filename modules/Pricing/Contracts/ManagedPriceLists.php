<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts;

use Modules\Pricing\Contracts\Data\ManagedPriceList;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.115): plugin đồng bộ nội dung của mình thành bảng giá sale/thành viên của Core, khoá theo
 * (plugin, khoá của plugin). Giá ghi qua cùng đường với Admin (lịch sử giá, audit, `PriceChanged`, cache trang). Giá thấp hơn
 * mức trần giảm so với giá niêm yết → ValidationException khoá `prices.<variant id>`. Admin → Bảng giá chỉ xem bảng giá này,
 * kèm liên kết sang màn hình của plugin. Mã bảng giá (`ResolvedPrice::$priceListCode`, dòng đơn) cố định theo (plugin, khoá).
 */
#[PluginApi]
interface ManagedPriceLists
{
    /** Tạo hoặc cập nhật; trả id bảng giá. */
    public function sync(string $owner, string $key, ManagedPriceList $list): int;

    /** Gỡ bảng giá (đơn đã đặt giữ giá và mã bảng giá đã chụp). */
    public function remove(string $owner, string $key): void;

    /**
     * @return array<int|string, array{id: int, code: string}> khoá của plugin => bảng giá
     */
    public function lists(string $owner): array;
}
