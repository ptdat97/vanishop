<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract: bộ sưu tập chứa từng style.
 * Rule khuyến mãi theo bộ sưu tập (`vani.promotion-rules`) dùng contract này.
 */
#[PluginApi]
interface CollectionDirectory
{
    /**
     * @param  list<int>  $styleIds
     * @return array<int, list<string>> style id => slug các bộ sưu tập đang hiển thị chứa style đó
     */
    public function slugsForStyles(array $styleIds): array;
}
