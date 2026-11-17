<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Modules\Identity\Contracts\StaffNames;
use Modules\Identity\Persistence\Models\StaffUser;

final class EloquentStaffNames implements StaffNames
{
    public function names(array $staffIds): array
    {
        if ($staffIds === []) {
            return [];
        }

        return StaffUser::query()->whereKey(array_values(array_unique($staffIds)))->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id): array => [(int) $id => (string) $name])->all();
    }
}
