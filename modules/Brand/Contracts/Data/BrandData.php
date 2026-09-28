<?php

declare(strict_types=1);

namespace Modules\Brand\Contracts\Data;

final readonly class BrandData
{
    public function __construct(
        public int $id,
        public int $legalEntityId,
        public string $code,
        public string $name,
        public string $slug,
    ) {}
}
