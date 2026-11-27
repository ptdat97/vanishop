<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerSummary;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.110): tìm và liệt kê khách đang hoạt động cho màn hình Admin của plugin — vd. phát mã giảm giá
 * riêng cho một khách, một nhóm khách hoặc khách có tag (`vani.coupon-code`). Không gồm khách đã gộp/ẩn danh hoá.
 */
#[PluginApi]
interface CustomerDirectory
{
    public const MAX_BATCH = 1000;

    /**
     * Theo SĐT (chuẩn hoá như ô tìm của Admin → Khách hàng), email hoặc tên; mới nhất trước.
     *
     * @return list<CustomerSummary>
     */
    public function search(string $query, int $limit = 20): array;

    /**
     * @param  list<int>  $customerIds
     * @return array<int, CustomerSummary> id => khách (id không tồn tại thì vắng)
     */
    public function summaries(array $customerIds): array;

    /**
     * Id khách theo nhóm và/hoặc tag, tăng dần, theo lô (keyset): gọi lại với `afterId` = id cuối của lô trước.
     *
     * @return list<int> tối đa `min($limit, MAX_BATCH)` id
     */
    public function customerIds(?int $groupId, ?string $tag, int $afterId = 0, int $limit = 500): array;
}
