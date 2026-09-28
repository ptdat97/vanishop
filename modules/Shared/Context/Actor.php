<?php

declare(strict_types=1);

namespace Modules\Shared\Context;

/**
 * Ai đang thực hiện hành động (dùng cho audit, order events, phân quyền).
 */
final readonly class Actor
{
    public function __construct(
        public ActorType $type,
        public ?int $id = null,
        public ?string $label = null,
    ) {}

    public static function guest(): self
    {
        return new self(ActorType::Guest);
    }

    public static function staff(int $id, ?string $label = null): self
    {
        return new self(ActorType::Staff, $id, $label);
    }

    public static function system(string $label): self
    {
        return new self(ActorType::System, null, $label);
    }
}
