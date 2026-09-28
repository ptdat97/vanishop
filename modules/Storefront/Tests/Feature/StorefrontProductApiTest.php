<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers;
use Modules\Pricing\Tests\Feature\PricingTestHelpers;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

beforeEach(function () {
    $this->headers = ['X-Vani-Channel' => 'web-lumiere'];
    $this->lumiere = Brand::factory()->create(['slug' => 'lumiere']);
    $this->urbanx = Brand::factory()->create(['slug' => 'urbanx']);
    Channel::factory()->forBrand($this->lumiere, 'vani.test', '/lumiere')->create(['code' => 'web-lumiere']);

    T::seed(function () {
        $this->women = Category::factory()->create(['brand_id' => $this->lumiere->id, 'slug' => 'nu']);
        $this->dresses = Category::factory()->childOf($this->women)->create(['slug' => 'dam']);
        $this->material = Attribute::factory()->create(['brand_id' => $this->lumiere->id, 'code' => 'material', 'input_type' => 'select', 'is_filterable' => true]);
        $this->material->syncTranslations(['vi' => ['name' => 'Chất liệu']]);
        $this->silk = $this->material->values()->create(['code' => 'silk']);
        $this->silk->syncTranslations(['vi' => ['label' => 'Lụa']]);
        $this->linen = $this->material->values()->create(['code' => 'linen']);
        $this->supplier = Attribute::factory()->internal()->create(['brand_id' => $this->lumiere->id, 'code' => 'supplier', 'input_type' => 'text']);
        $this->black = Color::factory()->create(['brand_id' => $this->lumiere->id, 'code' => 'BLK', 'color_family' => 'black']);
        $this->white = Color::factory()->create(['brand_id' => $this->lumiere->id, 'code' => 'WHT', 'color_family' => 'white']);
    });

    $this->silkDress = T::product($this->lumiere->id, ['name' => 'Đầm lụa đen', 'slug' => 'dam-lua-den', 'category_ids' => [$this->dresses->id], 'primary_category_id' => $this->dresses->id,
        'attributes' => [$this->material->id => $this->silk->id, $this->supplier->id => 'Xưởng A']]);
    $this->linenShirt = T::product($this->lumiere->id, ['name' => 'Áo linen trắng', 'slug' => 'ao-linen', 'category_ids' => [$this->women->id], 'attributes' => [$this->material->id => $this->linen->id]]);
    T::product($this->lumiere->id, ['name' => 'Đầm nháp', 'status' => 'draft']);
    T::product($this->lumiere->id, ['name' => 'Đầm sắp bán', 'published_from' => new DateTimeImmutable('+1 day')]);
    T::product($this->urbanx->id, ['name' => 'Đầm brand khác']);

    T::seed(function () {
        $this->silkDress->colors()->create(['color_id' => $this->black->id]);
        $this->linenShirt->colors()->create(['color_id' => $this->white->id]);
    });
});

it('chỉ trả sản phẩm đang hiển thị của các brand trong kênh', function () {
    $this->getJson('/api/storefront/v1/products', $this->headers)
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonCount(2, 'data');
});

it('tìm theo từ khoá không dấu', function () {
    $this->getJson('/api/storefront/v1/products?q=dam lua', $this->headers)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'dam-lua-den');
});

it('lọc theo danh mục cha gồm cả danh mục con', function () {
    $this->getJson('/api/storefront/v1/products?category=nu', $this->headers)->assertJsonPath('meta.total', 2);
    $this->getJson('/api/storefront/v1/products?category=dam', $this->headers)->assertJsonPath('meta.total', 1);
    $this->getJson('/api/storefront/v1/products?category=khong-co', $this->headers)->assertJsonPath('meta.total', 0);
});

it('lọc theo màu và thuộc tính, kèm facet', function () {
    $response = $this->getJson('/api/storefront/v1/products?color=black', $this->headers)->assertJsonPath('meta.total', 1);
    expect($response->json('meta.facets.color_families'))->toBe(['black' => 1, 'white' => 1]);

    $this->getJson('/api/storefront/v1/products?attr[material]=silk,linen', $this->headers)->assertJsonPath('meta.total', 2);
    $this->getJson('/api/storefront/v1/products?attr[material]=silk', $this->headers)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.facets.attributes.0.code', 'material')
        ->assertJsonPath('meta.facets.attributes.0.values.0.label', 'Lụa');
    $this->getJson('/api/storefront/v1/products?attr[supplier]=x', $this->headers)->assertJsonPath('meta.total', 0);
});

it('lọc theo bộ sưu tập và phân trang', function () {
    T::seed(function () {
        $collection = ProductCollection::query()->create(['brand_id' => $this->lumiere->id, 'slug' => 'he-2026', 'status' => 'active']);
        $collection->styles()->attach($this->linenShirt->id);
    });

    $this->getJson('/api/storefront/v1/products?collection=he-2026', $this->headers)->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.slug', 'ao-linen');
    $this->getJson('/api/storefront/v1/products?per_page=1&page=2', $this->headers)->assertJsonCount(1, 'data')->assertJsonPath('meta.page', 2);
});

it('PDP có thuộc tính spec, breadcrumb, màu; ẩn thuộc tính internal', function () {
    $response = $this->getJson('/api/storefront/v1/products/dam-lua-den', $this->headers)->assertOk();

    $response->assertJsonPath('data.name', 'Đầm lụa đen')
        ->assertJsonPath('data.attributes.0.code', 'material')
        ->assertJsonPath('data.attributes.0.value', 'Lụa')
        ->assertJsonPath('data.breadcrumb.0.slug', 'nu')
        ->assertJsonPath('data.breadcrumb.1.slug', 'dam')
        ->assertJsonPath('data.colors.0.code', 'BLK');
    expect(collect($response->json('data.attributes'))->pluck('code')->all())->not->toContain('supplier');
});

it('PDP trả 404 cho sản phẩm nháp, chưa tới giờ bán hoặc của brand khác', function () {
    $this->getJson('/api/storefront/v1/products/khong-ton-tai', $this->headers)->assertNotFound()->assertJsonPath('error.code', 'http.404');
});

it('validate tham số', function () {
    $this->getJson('/api/storefront/v1/products?per_page=500', $this->headers)->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});

it('trả khoảng giá ở danh sách và giá từng biến thể ở PDP theo bảng giá của kênh', function () {
    $channelId = Channel::query()->where('code', 'web-lumiere')->value('id');
    require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';
    [$s, $m] = PricingTestHelpers::variants($this->silkDress, ['S', 'M']);
    PricingTestHelpers::priceList($this->lumiere->id, ['code' => 'base'], [$channelId], [$s->id => [590_000], $m->id => [620_000]]);
    PricingTestHelpers::priceList($this->lumiere->id, ['code' => 'sale', 'type' => 'sale', 'priority' => 10], [$channelId], [$s->id => [413_000]]);

    $this->getJson('/api/storefront/v1/products?q=dam lua', $this->headers)
        ->assertJsonPath('data.0.price.min.amount', 413000)
        ->assertJsonPath('data.0.price.max.amount', 620000)
        ->assertJsonPath('data.0.price.min.formatted', '413.000 ₫')
        ->assertJsonPath('data.0.price.discount_percent', 30)
        ->assertJsonMissingPath('data.0.variant_ids');

    $this->getJson('/api/storefront/v1/products?q=linen', $this->headers)->assertJsonPath('data.0.price', null);

    $detail = $this->getJson('/api/storefront/v1/products/dam-lua-den', $this->headers)->assertOk();
    $variants = collect($detail->json('data.variants'))->keyBy('sku');
    expect($variants[$s->sku]['price']['amount']['amount'])->toBe(413000)
        ->and($variants[$s->sku]['price']['compare_at']['amount'])->toBe(590000)
        ->and($variants[$m->sku]['price']['amount']['amount'])->toBe(620000)
        ->and($variants[$m->sku]['price']['compare_at'])->toBeNull();
});

it('công bố còn hàng / sắp hết theo tồn của kênh, không lộ số lượng', function () {
    $channel = Channel::query()->where('code', 'web-lumiere')->sole();
    require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';
    require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';
    [$s, $m, $l] = PricingTestHelpers::variants($this->silkDress, ['S', 'M', 'L']);
    PricingTestHelpers::priceList($this->lumiere->id, ['code' => 'base'], [$channel->id], [$s->id => [590_000], $m->id => [590_000]]);
    $online = InventoryTestHelpers::location($this->lumiere, [$channel->id]);
    $storeOnly = InventoryTestHelpers::location($this->lumiere, []);
    InventoryTestHelpers::stock($online, $s->id, 20);
    InventoryTestHelpers::stock($online, $m->id, 4, 2);
    InventoryTestHelpers::stock($storeOnly, $m->id, 50);
    InventoryTestHelpers::stock($online, $l->id, 9);

    $this->getJson('/api/storefront/v1/products?q=dam lua', $this->headers)->assertJsonPath('data.0.in_stock', true);
    $this->getJson('/api/storefront/v1/products?q=linen', $this->headers)->assertJsonPath('data.0.in_stock', false);

    $detail = $this->getJson('/api/storefront/v1/products/dam-lua-den', $this->headers)->assertOk()->assertJsonPath('data.in_stock', true);
    $variants = collect($detail->json('data.variants'))->keyBy('sku');
    expect($variants[$s->sku])->toMatchArray(['available' => true, 'low_stock' => false])
        ->and($variants[$m->sku])->toMatchArray(['available' => true, 'low_stock' => true])
        ->and($variants[$l->sku])->toMatchArray(['available' => false, 'low_stock' => false])
        ->and(json_encode($detail->json()))->not->toContain('on_hand');
});
