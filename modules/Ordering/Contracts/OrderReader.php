<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderData;

/**
 * Service contract: đọc đơn (snapshot). Trong phạm vi brand của CurrentContext.
 */
interface OrderReader
{
    public function find(int $orderId): ?OrderData;

    public function findByPublicId(string $publicId): ?OrderData;
}
