<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts;

interface LegalEntityDirectory
{
    /**
     * @return list<array{id: int, code: string, name: string, tax_code: string}>
     */
    public function all(): array;
}
