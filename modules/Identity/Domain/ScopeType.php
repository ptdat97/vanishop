<?php

declare(strict_types=1);

namespace Modules\Identity\Domain;

/**
 * Phạm vi gán vai trò: toàn cửa hàng (owner) hoặc một kho/cửa hàng vật lý (location).
 * Không có phạm vi brand/pháp nhân (ADR-028).
 */
enum ScopeType: string
{
    case Owner = 'owner';
    case Location = 'location';
}
