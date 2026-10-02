<?php

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

beforeEach(function () {
    T::seed(function () {
        $women = Category::factory()->create(['slug' => 'nu']);
        $women->syncTranslations(['vi' => ['name' => 'Nữ'], 'en' => ['name' => 'Women']]);
        Category::factory()->childOf($women)->create(['slug' => 'dam']);
        $hidden = Category::factory()->create(['slug' => 'noi-bo', 'status' => 'hidden']);
        Category::factory()->childOf($hidden)->create(['slug' => 'con-cua-an']);
        Category::factory()->create(['slug' => 'streetwear']);
    });
});

it('trả cây danh mục đang hiển thị của cửa hàng', function () {
    $this->getJson('/api/storefront/v1/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'nu')
        ->assertJsonPath('data.0.name', 'Nữ')
        ->assertJsonPath('data.0.children.0.slug', 'dam')
        ->assertJsonMissing(['slug' => 'noi-bo'])
        ->assertJsonMissing(['slug' => 'con-cua-an'])
        ->assertJsonPath('data.1.slug', 'streetwear');
});

it('dịch theo X-Vani-Locale; Accept-Language của trình duyệt không đổi ngôn ngữ', function () {
    $this->getJson('/api/storefront/v1/categories', ['X-Vani-Locale' => 'en'])
        ->assertJsonPath('data.0.name', 'Women')
        ->assertJsonPath('data.0.children.0.name', 'Dam');

    $this->getJson('/api/storefront/v1/categories', ['Accept-Language' => 'en-US'])
        ->assertJsonPath('data.0.name', 'Nữ');
});

it('xem một danh mục theo slug; danh mục ẩn trả 404', function () {
    $this->getJson('/api/storefront/v1/categories/dam')->assertOk()->assertJsonPath('data.slug', 'dam');
    $this->getJson('/api/storefront/v1/categories/noi-bo')->assertNotFound();
});

it('lỗi 404 theo định dạng chuẩn', function () {
    $response = $this->getJson('/api/storefront/v1/categories/khong-co')->assertNotFound();

    $response->assertJsonPath('error.code', 'http.404')
        ->assertJsonStructure(['error' => ['code', 'message', 'correlation_id']]);
    expect($response->json('error.correlation_id'))->toBe($response->headers->get('X-Correlation-Id'));

});
