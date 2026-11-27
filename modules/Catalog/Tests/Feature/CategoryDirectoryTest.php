<?php

use Modules\Catalog\Contracts\CategoryDirectory;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

it('trả danh mục của product kèm mọi danh mục cha; product không có danh mục thì vắng', function () {
    [$women, $dresses, $party, $bags] = T::seed(function () {
        $women = Category::factory()->create(['slug' => 'nu']);
        $dresses = Category::factory()->create(['slug' => 'dam', 'parent_id' => $women->id]);

        return [$women, $dresses, Category::factory()->create(['slug' => 'dam-tiec', 'parent_id' => $dresses->id]), Category::factory()->create(['slug' => 'tui'])];
    });
    $dress = T::product(null, ['product_code' => 'DAM01', 'category_ids' => [$party->id, $bags->id], 'primary_category_id' => $party->id]);
    $plain = T::product(null, ['product_code' => 'AO01']);

    $ids = app(CategoryDirectory::class)->idsForProducts([$dress->id, $plain->id, $dress->id]);

    expect(array_keys($ids))->toBe([$dress->id])
        ->and(collect($ids[$dress->id])->sort()->values()->all())->toBe(collect([$women->id, $dresses->id, $party->id, $bags->id])->sort()->values()->all());
});
