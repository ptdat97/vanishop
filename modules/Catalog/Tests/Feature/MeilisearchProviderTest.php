<?php

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Application\Search\MeilisearchSearchProvider;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;

beforeEach(function () {
    $this->provider = new MeilisearchSearchProvider('http://meili.test:7700', 'secret', 'vani_products');
});

it('index tài liệu qua REST kèm token', function () {
    Http::fake(['meili.test:7700/*' => Http::response(['taskUid' => 1], 202)]);

    $this->provider->index(new ProductDocument(7, 1, 'LM01', 'dam', 'active', null, null, ['vi' => 'Đầm'], 'lm01 dam', [1, 2], [], ['black'], [5], 1_700_000_000));

    Http::assertSent(fn (Request $request) => $request->url() === 'http://meili.test:7700/indexes/vani_products/documents?primaryKey=id'
        && $request->hasHeader('Authorization', 'Bearer secret')
        && $request[0]['id'] === 7 && $request[0]['color_families'] === ['black']);
});

it('dựng bộ lọc: brand, trạng thái, khung giờ, OR trong nhóm, AND giữa các nhóm', function () {
    Http::fake(['meili.test:7700/*' => Http::response([
        'hits' => [['id' => 3], ['id' => 9]],
        'estimatedTotalHits' => 2,
        'facetDistribution' => ['color_families' => ['black' => 2], 'attribute_value_ids' => ['5' => 1]],
    ])]);

    $result = $this->provider->search(new ProductSearchQuery(
        brandIds: [1, 2], now: 1_800_000_000, text: 'Đầm Lụa', categoryId: 4,
        colorFamilies: ['black', 'white'], attributeValueIds: [10 => [5, 6]],
    ));

    expect($result->styleIds)->toBe([3, 9])
        ->and($result->total)->toBe(2)
        ->and($result->facets['attribute_values'])->toBe([5 => 1]);

    Http::assertSent(function (Request $request) {
        $filter = $request['filter'];

        return $request['q'] === 'dam lua'
            && $filter[0] === 'brand_id IN [1, 2]'
            && in_array('category_ids = 4', $filter, true)
            && in_array(["color_families = 'black'", "color_families = 'white'"], $filter, true)
            && in_array(['attribute_value_ids = 5', 'attribute_value_ids = 6'], $filter, true);
    });
});

it('ném lỗi khi Meilisearch lỗi (để queue retry)', function () {
    Http::fake(['meili.test:7700/*' => Http::response(['message' => 'down'], 503)]);

    $this->provider->remove(3);
})->throws(RequestException::class);
