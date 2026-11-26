<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\VariantIndexRows;
use Modules\Catalog\Contracts\VariantDirectory;

/**
 * Ghi read model `price_variant_index` (db-refactor L55) theo sản phẩm khi Catalog dựng read model của mình (event
 * ProductListingProjected). Dựng toàn bộ: `vani:catalog:rebuild-listing`.
 */
final class PriceVariantIndexer
{
    private const CHUNK = 500;

    public function __construct(private readonly VariantDirectory $variants) {}

    /**
     * @param  list<int>  $productIds
     */
    public function syncProducts(array $productIds): void
    {
        foreach (array_chunk(array_values(array_unique($productIds)), self::CHUNK) as $chunk) {
            $rows = array_map(function (array $row): array {
                unset($row['options'], $row['tracked']);

                return $row;
            }, VariantIndexRows::from($this->variants->ofProducts($chunk)));
            DB::transaction(function () use ($chunk, $rows): void {
                DB::table('price_variant_index')->whereIn('product_id', $chunk)->delete();
                DB::table('price_variant_index')->whereIn('variant_id', array_column($rows, 'variant_id'))->delete();
                foreach (array_chunk($rows, 1000) as $part) {
                    DB::table('price_variant_index')->insert($part);
                }
            });
        }
    }
}
