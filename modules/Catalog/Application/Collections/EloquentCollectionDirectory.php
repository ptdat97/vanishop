<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Collections;

use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Catalog\Persistence\Models\ProductCollection;

final class EloquentCollectionDirectory implements CollectionDirectory
{
    public function slugsForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if ($productIds === []) {
            return [];
        }

        $rows = ProductCollection::query()
            ->join('collection_product', 'collection_product.collection_id', '=', 'collections.id')
            ->where('collections.status', 'active')
            ->whereIn('collection_product.product_id', $productIds)
            ->get(['collection_product.product_id', 'collections.slug']);

        $collections = [];
        foreach ($rows as $row) {
            $collections[(int) $row->getAttribute('product_id')][] = (string) $row->slug;
        }

        ksort($collections);

        return $collections;
    }
}
