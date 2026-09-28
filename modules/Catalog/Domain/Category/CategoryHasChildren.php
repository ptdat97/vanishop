<?php

declare(strict_types=1);

namespace Modules\Catalog\Domain\Category;

use DomainException;

final class CategoryHasChildren extends DomainException
{
    public function __construct()
    {
        parent::__construct('Không thể xoá danh mục còn danh mục con.');
    }
}
