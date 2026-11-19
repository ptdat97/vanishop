<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Bên gửi không phải authority tồn vật lý của location (hoặc location không tồn tại).
 */
final class NotStockAuthority extends BusinessRuleViolation
{
    public function __construct(public readonly string $locationCode)
    {
        parent::__construct(__('inventory::texts.not_stock_authority.not_stock_authority_location_location', ['location_code' => $locationCode]));
    }

    public function errorCode(): string
    {
        return 'not_data_owner';
    }

    public function status(): int
    {
        return 403;
    }

    public function details(): array
    {
        return ['location_code' => $this->locationCode];
    }
}
