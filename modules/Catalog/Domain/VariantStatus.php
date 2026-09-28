<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

enum VariantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
