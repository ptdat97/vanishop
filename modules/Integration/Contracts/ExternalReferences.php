<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

/**
 * Service contract: ánh xạ định danh nội bộ ↔ định danh ở hệ thống ngoài (số đơn ↔ số chứng từ ERP…).
 */
interface ExternalReferences
{
    public function link(string $system, string $entityType, string $internalId, string $externalId): void;

    public function externalId(string $system, string $entityType, string $internalId): ?string;

    public function internalId(string $system, string $entityType, string $externalId): ?string;
}
