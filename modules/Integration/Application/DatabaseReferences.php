<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Support\Facades\DB;
use Modules\Integration\Contracts\ExternalReferences;
use Modules\Integration\Contracts\MappingMissing;
use Modules\Integration\Contracts\Mappings;

final class DatabaseReferences implements ExternalReferences, Mappings
{
    public function link(string $system, string $entityType, string $internalId, string $externalId): void
    {
        DB::table('external_references')->upsert(
            [['system' => $system, 'entity_type' => $entityType, 'internal_id' => $internalId, 'external_id' => $externalId, 'created_at' => now(), 'updated_at' => now()]],
            ['system', 'entity_type', 'internal_id'],
            ['external_id', 'updated_at'],
        );
    }

    public function externalId(string $system, string $entityType, string $internalId): ?string
    {
        $value = DB::table('external_references')->where(['system' => $system, 'entity_type' => $entityType, 'internal_id' => $internalId])->value('external_id');

        return $value === null ? null : (string) $value;
    }

    public function internalId(string $system, string $entityType, string $externalId): ?string
    {
        $value = DB::table('external_references')->where(['system' => $system, 'entity_type' => $entityType, 'external_id' => $externalId])->value('internal_id');

        return $value === null ? null : (string) $value;
    }

    public function map(string $system, string $type, string $internalValue): string
    {
        $value = DB::table('integration_mappings')->where(['system' => $system, 'type' => $type, 'internal_value' => $internalValue])->value('external_value');
        if ($value === null) {
            throw new MappingMissing($system, $type, $internalValue);
        }

        return (string) $value;
    }

    public function put(string $system, string $type, string $internalValue, string $externalValue): void
    {
        DB::table('integration_mappings')->upsert(
            [['system' => $system, 'type' => $type, 'internal_value' => $internalValue, 'external_value' => $externalValue, 'created_at' => now(), 'updated_at' => now()]],
            ['system', 'type', 'internal_value'],
            ['external_value', 'updated_at'],
        );
    }
}
