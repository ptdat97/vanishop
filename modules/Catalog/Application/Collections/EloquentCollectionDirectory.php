<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Collections;

use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Catalog\Persistence\Models\ProductCollection;

final class EloquentCollectionDirectory implements CollectionDirectory
{
    public function slugsForStyles(array $styleIds): array
    {
        $styleIds = array_values(array_unique(array_map('intval', $styleIds)));
        if ($styleIds === []) {
            return [];
        }

        // ProductCollection có BrandScope nên chỉ trả bộ sưu tập trong phạm vi brand hiện tại.
        $rows = ProductCollection::query()
            ->join('collection_style', 'collection_style.collection_id', '=', 'collections.id')
            ->where('collections.status', 'active')
            ->whereIn('collection_style.style_id', $styleIds)
            ->get(['collection_style.style_id', 'collections.slug']);

        $collections = [];
        foreach ($rows as $row) {
            $collections[(int) $row->style_id][] = (string) $row->slug;
        }

        ksort($collections);

        return $collections;
    }
}
