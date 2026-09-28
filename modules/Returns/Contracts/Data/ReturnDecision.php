<?php

declare(strict_types=1);

namespace Modules\Returns\Contracts\Data;

final readonly class ReturnDecision
{
    public function __construct(
        public bool $eligible,
        public ?string $reason = null,
        /** Duyệt ngay không cần CSKH. */
        public bool $autoApprove = false,
    ) {}

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
