<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application;

use Modules\Tenancy\Contracts\LegalEntityDirectory;
use Modules\Tenancy\Persistence\Models\LegalEntity;

final class EloquentLegalEntityDirectory implements LegalEntityDirectory
{
    public function all(): array
    {
        return LegalEntity::query()->orderBy('name')->get(['id', 'code', 'name', 'tax_code'])
            ->map(fn (LegalEntity $entity): array => ['id' => $entity->id, 'code' => $entity->code, 'name' => $entity->name, 'tax_code' => $entity->tax_code])
            ->all();
    }
}
