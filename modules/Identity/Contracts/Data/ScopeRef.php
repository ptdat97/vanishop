<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts\Data;

use Modules\Identity\Domain\ScopeType;

/**
 * Phạm vi của đối tượng cần kiểm tra quyền.
 */
final readonly class ScopeRef
{
    public function __construct(
        public ScopeType $type,
        public ?int $id = null,
    ) {}

    public static function owner(): self
    {
        return new self(ScopeType::Owner);
    }

    public static function location(int $locationId): self
    {
        return new self(ScopeType::Location, $locationId);
    }
}
