<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

enum CategoryStatus: string
{
    case Active = 'active';
    case Hidden = 'hidden';
}
