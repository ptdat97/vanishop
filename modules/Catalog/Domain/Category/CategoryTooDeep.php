<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Category;

use DomainException;

final class CategoryTooDeep extends DomainException
{
    public function __construct(int $maxDepth)
    {
        parent::__construct("Danh mục tối đa {$maxDepth} cấp.");
    }
}
