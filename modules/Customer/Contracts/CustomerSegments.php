<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerSegment;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.3.35): nhóm + tag của khách, cho rule khuyến mãi, báo cáo, plugin marketing.
 */
#[PluginApi]
interface CustomerSegments
{
    public function segmentOf(int $customerId): CustomerSegment;

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public function groups(): array;

    /**
     * Gắn nhãn cho khách (EP v3 X6 — vd. `bom-hang`, `vip`); nhãn được chuẩn hoá dạng slug; đã có thì bỏ qua. Ghi audit
     * kèm `source` (`staff` | `system:<id plugin>`).
     */
    public function tag(int $customerId, string $tag, string $source): void;

    public function untag(int $customerId, string $tag, string $source): void;

    /**
     * Chuyển nhóm khách (hưởng bảng giá thành viên của nhóm); null = bỏ nhóm. Nhóm không tồn tại → ValidationException.
     */
    public function assignGroup(int $customerId, ?int $groupId, string $source): void;
}
