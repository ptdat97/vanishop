<?php

use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers;
use Modules\Pricing\Tests\Feature\PricingTestHelpers;
use Modules\Storefront\Contracts\ProductCards;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

beforeEach(function () {
    $this->headers = [];
    $this->lumiere = Brand::factory()->create(['slug' => 'lumiere']);
    $this->urbanx = Brand::factory()->create(['slug' => 'urbanx']);

    T::seed(function () {
        $this->women = Category::factory()->create(['slug' => 'nu']);
        $this->dresses = Category::factory()->childOf($this->women)->create(['slug' => 'dam']);
        $this->material = Attribute::factory()->create(['code' => 'material', 'input_type' => 'select', 'is_filterable' => true]);
        $this->material->syncTranslations(['vi' => ['name' => 'Chất liệu']]);
        $this->silk = $this->material->values()->create(['code' => 'silk']);
        $this->silk->syncTranslations(['vi' => ['label' => 'Lụa']]);
        $this->linen = $this->material->values()->create(['code' => 'linen']);
        $this->supplier = Attribute::factory()->internal()->create(['code' => 'supplier', 'input_type' => 'text']);
        $this->black = OptionValue::factory()->color()->create(['code' => 'BLK', 'family' => 'black']);
        $this->white = OptionValue::factory()->color()->create(['code' => 'WHT', 'family' => 'white']);
    });

    $this->silkDress = T::product($this->lumiere->id, ['name' => 'Đầm lụa đen', 'slug' => 'dam-lua-den', 'category_ids' => [$this->dresses->id], 'primary_category_id' => $this->dresses->id,
        'attributes' => [$this->material->id => $this->silk->id, $this->supplier->id => 'Xưởng A']]);
    $this->linenShirt = T::product($this->lumiere->id, ['name' => 'Áo linen trắng', 'slug' => 'ao-linen', 'category_ids' => [$this->women->id], 'attributes' => [$this->material->id => $this->linen->id]]);
    T::product($this->lumiere->id, ['name' => 'Đầm nháp', 'status' => 'draft']);
    T::product($this->lumiere->id, ['name' => 'Đầm sắp bán', 'published_from' => new DateTimeImmutable('+1 day')]);
    T::product($this->urbanx->id, ['name' => 'Áo thun Urbanx', 'slug' => 'ao-thun-urbanx']);

    T::optionValues($this->silkDress, $this->black);
    T::optionValues($this->linenShirt, $this->white);
});

it('trả sản phẩm đang hiển thị của mọi thương hiệu', function () {
    $this->getJson('/api/storefront/v1/products', $this->headers)
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonCount(3, 'data');
});

it('lọc theo thương hiệu, kèm facet thương hiệu và thông tin brand trên sản phẩm', function () {
    $response = $this->getJson('/api/storefront/v1/products?brand=urbanx', $this->headers)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'ao-thun-urbanx')
        ->assertJsonPath('data.0.brand.slug', 'urbanx');
    expect(collect($response->json('meta.facets.brands'))->pluck('count', 'slug')->sortKeys()->all())->toBe(['lumiere' => 2, 'urbanx' => 1]);

    $this->getJson('/api/storefront/v1/products?brand=lumiere,urbanx', $this->headers)->assertJsonPath('meta.total', 3);
    $this->getJson('/api/storefront/v1/products?brand=khong-co', $this->headers)->assertJsonPath('meta.total', 0);
    $this->getJson('/api/storefront/v1/products/dam-lua-den', $this->headers)->assertJsonPath('data.brand.name', $this->lumiere->name);
});

it('danh sách và chi tiết thương hiệu; brand ẩn trả 404', function () {
    $this->getJson('/api/storefront/v1/brands')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/storefront/v1/brands/urbanx')->assertOk()->assertJsonPath('data.slug', 'urbanx');

    $this->urbanx->update(['status' => 'hidden']);
    $this->getJson('/api/storefront/v1/brands')->assertJsonCount(1, 'data');
    $this->getJson('/api/storefront/v1/brands/urbanx')->assertNotFound();
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
        $collection = ProductCollection::query()->create(['slug' => 'he-2026', 'status' => 'active']);
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
        ->assertJsonPath('data.breadcrumb.1.id', $this->dresses->id)
        ->assertJsonPath('data.brand.id', $this->lumiere->id)
        // Màu chưa có SKU: không là giá trị chọn được ở trang chi tiết; thẻ sản phẩm (danh sách) vẫn có swatch.
        ->assertJsonPath('data.options.0.values', []);
    $this->getJson("/api/storefront/v1/products?ids={$this->silkDress->id}", $this->headers)->assertJsonPath('data.0.colors.0.code', 'BLK');
    expect(collect($response->json('data.attributes'))->pluck('code')->all())->not->toContain('supplier');
});

it('PDP trả 404 cho sản phẩm không tồn tại', function () {
    $this->getJson('/api/storefront/v1/products/khong-ton-tai', $this->headers)->assertNotFound()->assertJsonPath('error.code', 'http.404');
});

it('validate tham số', function () {
    $this->getJson('/api/storefront/v1/products?per_page=500', $this->headers)->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});

it('trả khoảng giá ở danh sách và giá từng biến thể ở PDP theo bảng giá', function () {
    require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';
    [$s, $m] = PricingTestHelpers::variants($this->silkDress, ['S', 'M']);
    PricingTestHelpers::priceList(['code' => 'base'], [$s->id => [590_000], $m->id => [620_000]]);
    PricingTestHelpers::priceList(['code' => 'sale', 'type' => 'sale', 'priority' => 10], [$s->id => [413_000]]);

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

it('công bố còn hàng / sắp hết theo tồn giao online, không lộ số lượng', function () {
    require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';
    require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';
    [$s, $m, $l] = PricingTestHelpers::variants($this->silkDress, ['S', 'M', 'L']);
    PricingTestHelpers::priceList(['code' => 'base'], [$s->id => [590_000], $m->id => [590_000]]);
    $online = InventoryTestHelpers::location();
    $storeOnly = InventoryTestHelpers::location(['ships_online_orders' => false]);
    InventoryTestHelpers::stock($online, $s->id, 20);
    InventoryTestHelpers::stock($online, $m->id, 4, 2);
    InventoryTestHelpers::stock($storeOnly, $m->id, 50);
    InventoryTestHelpers::stock($online, $l->id, 9);

    $this->getJson('/api/storefront/v1/products?q=dam lua', $this->headers)->assertJsonPath('data.0.in_stock', true);
    $this->getJson('/api/storefront/v1/products?q=linen', $this->headers)->assertJsonPath('data.0.in_stock', false);

    $detail = $this->getJson('/api/storefront/v1/products/dam-lua-den', $this->headers)->assertOk()->assertJsonPath('data.in_stock', true);
    $variants = collect($detail->json('data.variants'))->keyBy('sku');
    expect($variants[$s->sku])->toMatchArray(['availability' => 'in_stock', 'is_active' => true])
        ->and($variants[$m->sku])->toMatchArray(['availability' => 'low_stock'])
        ->and($variants[$l->sku])->toMatchArray(['availability' => 'out_of_stock'])
        ->and(json_encode($detail->json()))->not->toContain('on_hand')->not->toContain('available_quantity')
        ->and($detail->json('data.inventory.availability_status'))->toBe('in_stock');
});

it('mô hình options/variants: mọi SKU (cả ngừng bán, giá/trạng thái null), ánh xạ giá trị bằng id, đúng một SKU mặc định, ảnh theo SKU', function () {
    require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';
    require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';
    [$s, $m] = PricingTestHelpers::variants($this->silkDress, ['S', 'M']);
    PricingTestHelpers::priceList(['code' => 'base'], [$s->id => [590_000], $m->id => [590_000]]);
    InventoryTestHelpers::stock(InventoryTestHelpers::location(), $s->id, 5);
    T::seed(fn () => $m->update(['status' => 'inactive']));

    $data = $this->getJson('/api/storefront/v1/products/dam-lua-den', $this->headers)->assertOk()->json('data');
    $variants = collect($data['variants'])->keyBy('sku');
    $options = collect($data['options'])->keyBy('code');
    $valueId = fn (array $variant, string $option): ?int => collect($variant['option_values'])->firstWhere('option', $option)['value_id'] ?? null;

    expect($variants->keys()->all())->toContain($s->sku, $m->sku)
        ->and($variants[$m->sku])->toMatchArray(['is_active' => false, 'price' => null, 'availability' => null, 'is_default' => false])
        ->and($variants->where('is_default', true)->pluck('sku')->all())->toBe([$s->sku])
        ->and($options['color']['display_type'])->toBe('swatch')
        ->and($options['size']['display_type'])->toBe('button')
        ->and(collect($options['color']['values'])->firstWhere('id', $valueId($variants[$s->sku], 'color')))->not->toBeNull()
        ->and(collect($options['size']['values'])->firstWhere('id', $valueId($variants[$s->sku], 'size'))['code'])->toBe(T::seed(fn () => $s->valueOf('size')?->code))
        ->and($data)->not->toHaveKey('colors')->not->toHaveKey('default_quantity')
        ->and($data['purchase'])->toMatchArray(['min_quantity' => 1, 'is_purchasable' => true]);
    foreach ($variants as $variant) {
        expect(array_diff($variant['media']['image_ids'], array_column($data['media']['gallery'], 'id')))->toBe([]);
    }
});

it('thẻ sản phẩm theo id: giữ thứ tự, bỏ sản phẩm không hiển thị/trùng, cùng dạng lưới; giới hạn 30 id', function () {
    $draft = T::seed(fn () => Style::query()->where('status', 'draft')->value('id'));
    $ids = implode(',', [$this->linenShirt->id, $draft, 999999, $this->silkDress->id, $this->linenShirt->id]);

    $response = $this->getJson("/api/storefront/v1/products?ids={$ids}&brand=urbanx")->assertOk();
    expect(array_column($response->json('data'), 'slug'))->toBe(['ao-linen', 'dam-lua-den'])
        ->and($response->json('meta.total'))->toBe(2)
        ->and(array_keys($response->json('data.0')))->toEqualCanonicalizing(array_keys($this->getJson('/api/storefront/v1/products?q=linen')->json('data.0')));

    $this->getJson('/api/storefront/v1/products?ids='.implode(',', range(1, 31)))->assertStatus(422);
    $this->getJson('/api/storefront/v1/products?ids=1,abc')->assertStatus(422);

    // Contract cho plugin: cùng kết quả.
    expect(array_column(app(ProductCards::class)->forIds([$this->silkDress->id, $this->linenShirt->id], 'vi'), 'slug'))->toBe(['dam-lua-den', 'ao-linen']);
});
