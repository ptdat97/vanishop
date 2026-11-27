<?php

declare(strict_types=1);

namespace Modules\Catalog\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\CategoryDirectory;

final class EloquentCategoryDirectory implements CategoryDirectory
{
    public function idsForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if ($productIds === []) {
            return [];
        }

        $assigned = DB::table('category_product')->whereIn('product_id', $productIds)->get(['product_id', 'category_id']);
        if ($assigned->isEmpty()) {
            return [];
        }
        // Cây danh mục nhỏ (vài trăm nút): nạp một lần để lần lên danh mục cha.
        $parents = DB::table('categories')->pluck('parent_id', 'id')->map(fn (mixed $parent): ?int => $parent === null ? null : (int) $parent)->all();

        $result = [];
        foreach ($assigned as $row) {
            $categoryId = (int) $row->category_id;
            $seen = [];
            while ($categoryId !== null && ! isset($seen[$categoryId])) {
                $seen[$categoryId] = true;
                $result[(int) $row->product_id][$categoryId] = $categoryId;
                $categoryId = $parents[$categoryId] ?? null;
            }
        }
        ksort($result);

        return array_map(fn (array $ids): array => array_values($ids), $result);
    }
}
