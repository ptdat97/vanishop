<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

/**
 * Service contract: bộ sưu tập chứa từng style (trong phạm vi brand của CurrentContext).
 * Rule khuyến mãi theo bộ sưu tập (`vani.promotion-rules`) dùng contract này.
 */
interface CollectionDirectory
{
    /**
     * @param  list<int>  $styleIds
     * @return array<int, list<string>> style id => slug các bộ sưu tập đang hiển thị chứa style đó
     */
    public function slugsForStyles(array $styleIds): array;
}
