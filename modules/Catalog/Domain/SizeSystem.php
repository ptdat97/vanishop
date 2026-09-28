<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain;

enum SizeSystem: string
{
    case Alpha = 'alpha';      // XS, S, M, L, XL
    case Numeric = 'numeric';  // 26, 27, 28…
    case Vn = 'vn';
    case Us = 'us';
    case Eu = 'eu';
    case One = 'one';          // free size / không có size
}
