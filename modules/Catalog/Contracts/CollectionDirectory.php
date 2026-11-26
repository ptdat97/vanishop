<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract: bộ sưu tập chứa từng product.
 * Rule khuyến mãi theo bộ sưu tập (`vani.promotion-rules`) dùng contract này.
 */
#[PluginApi]
interface CollectionDirectory
{
    /**
     * @param  list<int>  $productIds
     * @return array<int, list<string>> product id => slug các bộ sưu tập đang hiển thị chứa product đó
     */
    public function slugsForProducts(array $productIds): array;
}
