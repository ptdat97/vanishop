<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Category;

use DomainException;

final class CategoryCycle extends DomainException
{
    public function __construct()
    {
        parent::__construct('Không thể chuyển danh mục vào chính nó hoặc danh mục con của nó.');
    }
}
