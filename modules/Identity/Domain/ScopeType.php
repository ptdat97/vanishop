<?php

declare(strict_types=1);

namespace Modules\Identity\Domain;

/**
 * Phạm vi gán vai trò. Location sẽ được thêm cùng module Inventory.
 */
enum ScopeType: string
{
    case Owner = 'owner';
    case LegalEntity = 'legal_entity';
    case Brand = 'brand';
}
