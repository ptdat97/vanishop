<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (0.5.110): danh mục chứa từng product, kèm mọi danh mục cha — product trong "Đầm dự tiệc" cũng thuộc
 * "Đầm". Rule khuyến mãi theo danh mục (`vani.promotion-rules` `in_categories`) dùng contract này; một truy vấn cho cả giỏ.
 */
#[PluginApi]
interface CategoryDirectory
{
    /**
     * @param  list<int>  $productIds
     * @return array<int, list<int>> product id => id danh mục (gán trực tiếp và danh mục cha), product không có danh mục thì vắng
     */
    public function idsForProducts(array $productIds): array;
}
