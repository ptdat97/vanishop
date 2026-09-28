<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

beforeEach(function () {
    $this->lumiere = Brand::factory()->create(['slug' => 'lumiere']);
    $this->urbanx = Brand::factory()->create(['slug' => 'urbanx']);
    Channel::factory()->forBrand($this->lumiere, 'vani.test', '/lumiere')->create(['code' => 'web-lumiere']);

    T::seed(function () {
        $women = Category::factory()->create(['brand_id' => $this->lumiere->id, 'slug' => 'nu']);
        $women->syncTranslations(['vi' => ['name' => 'Nữ'], 'en' => ['name' => 'Women']]);
        Category::factory()->childOf($women)->create(['slug' => 'dam']);
        $hidden = Category::factory()->create(['brand_id' => $this->lumiere->id, 'slug' => 'noi-bo', 'status' => 'hidden']);
        Category::factory()->childOf($hidden)->create(['slug' => 'con-cua-an']);
        Category::factory()->create(['brand_id' => $this->urbanx->id, 'slug' => 'streetwear']);
    });
});

it('trả cây danh mục đang hiển thị của kênh', function () {
    $this->getJson('/api/storefront/v1/categories', ['X-Vani-Channel' => 'web-lumiere'])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'nu')
        ->assertJsonPath('data.0.name', 'Nữ')
        ->assertJsonPath('data.0.children.0.slug', 'dam')
        ->assertJsonMissing(['slug' => 'noi-bo'])
        ->assertJsonMissing(['slug' => 'con-cua-an'])
        ->assertJsonMissing(['slug' => 'streetwear']);
});

it('dịch theo X-Vani-Locale; Accept-Language của trình duyệt không đổi ngôn ngữ kênh', function () {
    $this->getJson('/api/storefront/v1/categories', ['X-Vani-Channel' => 'web-lumiere', 'X-Vani-Locale' => 'en'])
        ->assertJsonPath('data.0.name', 'Women')
        ->assertJsonPath('data.0.children.0.name', 'Dam');

    $this->getJson('/api/storefront/v1/categories', ['X-Vani-Channel' => 'web-lumiere', 'Accept-Language' => 'en-US'])
        ->assertJsonPath('data.0.name', 'Nữ');
});

it('xem một danh mục theo slug; danh mục ẩn hoặc của brand khác trả 404', function () {
    $headers = ['X-Vani-Channel' => 'web-lumiere'];

    $this->getJson('/api/storefront/v1/categories/dam', $headers)->assertOk()->assertJsonPath('data.slug', 'dam');
    $this->getJson('/api/storefront/v1/categories/noi-bo', $headers)->assertNotFound();
    $this->getJson('/api/storefront/v1/categories/streetwear', $headers)->assertNotFound();
});

it('thiếu hoặc sai mã kênh trả lỗi theo định dạng chuẩn', function () {
    $response = $this->getJson('/api/storefront/v1/categories')->assertNotFound();

    $response->assertJsonPath('error.code', 'http.404')
        ->assertJsonStructure(['error' => ['code', 'message', 'correlation_id']]);
    expect($response->json('error.correlation_id'))->toBe($response->headers->get('X-Correlation-Id'));

    $this->getJson('/api/storefront/v1/categories', ['X-Vani-Channel' => 'khong-co'])->assertNotFound();
});
