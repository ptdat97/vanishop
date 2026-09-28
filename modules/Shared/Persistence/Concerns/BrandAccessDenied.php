<?php

declare(strict_types=1);

namespace Modules\Shared\Persistence\Concerns;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class BrandAccessDenied extends AccessDeniedHttpException
{
    public function __construct(?int $brandId)
    {
        parent::__construct($brandId === null
            ? 'Bản ghi thiếu brand_id.'
            : "Không có quyền với dữ liệu của brand #{$brandId}.");
    }
}
