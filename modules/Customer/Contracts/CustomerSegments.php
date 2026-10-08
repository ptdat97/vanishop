<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\CustomerSegment;

/**
 * Service contract (0.3.35): nhóm + tag của khách, cho rule khuyến mãi, báo cáo, plugin marketing.
 */
interface CustomerSegments
{
    public function segmentOf(int $customerId): CustomerSegment;

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public function groups(): array;
}
