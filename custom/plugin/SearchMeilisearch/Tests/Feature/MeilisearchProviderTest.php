<?php

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Contracts\ConfigurableSearchIndex;
use Modules\Catalog\Contracts\Data\ProductDocument;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Plugin\SearchMeilisearch\Infrastructure\MeilisearchSearchProvider;
use Plugin\SearchMeilisearch\SearchMeilisearchServiceProvider;

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
        'facetDistribution' => ['color_families' => ['black' => 2], 'attribute_value_ids' => ['5' => 1], 'brand_id' => ['1' => 2]],
    ])]);

    $result = $this->provider->search(new ProductSearchQuery(
        brandIds: [1, 2], now: 1_800_000_000, text: 'Đầm Lụa', categoryId: 4,
        colorFamilies: ['black', 'white'], attributeValueIds: [10 => [5, 6]],
    ));

    expect($result->styleIds)->toBe([3, 9])
        ->and($result->total)->toBe(2)
        ->and($result->facets['attribute_values'])->toBe([5 => 1])
        ->and($result->facets['brands'])->toBe([1 => 2]);

    Http::assertSent(function (Request $request) {
        $filter = $request['filter'];

        return $request['q'] === 'dam lua'
            && $filter[0] === "status = 'active'"
            && in_array('brand_id IN [1, 2]', $filter, true)
            && in_array('category_ids = 4', $filter, true)
            && in_array(["color_families = 'black'", "color_families = 'white'"], $filter, true)
            && in_array(['attribute_value_ids = 5', 'attribute_value_ids = 6'], $filter, true);
    });
});

it('không lọc thương hiệu khi danh sách brand rỗng', function () {
    Http::fake(['meili.test:7700/*' => Http::response(['hits' => [], 'estimatedTotalHits' => 0])]);

    $this->provider->search(new ProductSearchQuery(brandIds: [], now: 1_800_000_000));

    Http::assertSent(fn (Request $request) => collect($request['filter'])->flatten()->filter(fn (string $f) => str_starts_with($f, 'brand_id'))->isEmpty());
});

it('ném lỗi khi Meilisearch lỗi (để queue retry)', function () {
    Http::fake(['meili.test:7700/*' => Http::response(['message' => 'down'], 503)]);

    $this->provider->remove(3);
})->throws(RequestException::class);

it('VANI_SEARCH_PROVIDER=meilisearch nhưng plugin chưa bật → Core dùng provider database', function () {
    config(['vanishop.search.provider' => 'meilisearch']);

    expect(app(SearchManager::class)->provider()->code())->toBe('database');
});

it('plugin bật ở owner → SearchManager dùng Meilisearch; provider hỗ trợ cấu hình chỉ mục', function () {
    config(['vanishop.search.provider' => 'meilisearch']);
    CatalogTestHelpers::seed(function () {
        app(PluginManager::class)->install('vani.search-meilisearch');
        app(PluginManager::class)->enable('vani.search-meilisearch', 'owner');
    });
    app()->register(SearchMeilisearchServiceProvider::class);
    app(PluginActivation::class)->flush();

    $provider = app(SearchManager::class)->provider();
    expect($provider)->toBeInstanceOf(MeilisearchSearchProvider::class)
        ->and($provider)->toBeInstanceOf(ConfigurableSearchIndex::class);
});
