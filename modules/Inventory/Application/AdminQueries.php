<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Inventory\Persistence\Models\Location;

/**
 * Truy vấn đọc cho màn hình Admin của Inventory — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    public function location(int $id): Location
    {
        return Location::query()->findOrFail($id);
    }
}
