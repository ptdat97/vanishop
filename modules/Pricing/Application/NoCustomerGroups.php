<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Modules\Pricing\Contracts\CustomerGroupDirectory;

/**
 * Mặc định khi không có module Customer: không có nhóm khách nào.
 */
final class NoCustomerGroups implements CustomerGroupDirectory
{
    public function groupOf(int $customerId): ?int
    {
        return null;
    }

    public function groups(): array
    {
        return [];
    }
}
