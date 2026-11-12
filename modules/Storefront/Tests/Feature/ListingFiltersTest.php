<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Tenancy\Contracts\Settings;

beforeEach(function () {
    $this->lumiere = Brand::factory()->create(['slug' => 'lumiere', 'name' => 'Lumière']);
    $this->urbanx = Brand::factory()->create(['slug' => 'urbanx', 'name' => 'Urbanx']);
    T::seed(function () {
        $this->dresses = Category::factory()->create(['slug' => 'dam-vay']);
        $this->dresses->syncTranslations(['vi' => ['name' => 'Đầm váy'], 'en' => ['name' => 'Dresses', 'slug' => 'dresses']]);
        $this->material = Attribute::factory()->create(['code' => 'material', 'input_type' => 'select', 'is_filterable' => true]);
        $this->material->syncTranslations(['vi' => ['name' => 'Chất liệu']]);
        $this->silk = $this->material->values()->create(['code' => 'silk']);
        $this->silk->syncTranslations(['vi' => ['label' => 'Lụa']]);
    });
    $dress = T::product($this->lumiere->id, ['name' => 'Đầm lụa đen', 'slug' => 'dam-lua-den', 'category_ids' => [$this->dresses->id], 'attributes' => [$this->material->id => $this->silk->id]]);
    [$variant] = P::variants($dress, ['S']);
    T::seed(fn () => $variant->valueOf('color')->update(['family' => 'black']));
    P::priceList(['code' => 'base'], [$variant->id => [300_000]]);
    [$shirt] = P::variants(T::product($this->urbanx->id, ['name' => 'Áo thun Urbanx', 'slug' => 'ao-thun-urbanx']), ['M']);
    P::priceList(['code' => 'base-2'], [$shirt->id => [150_000]]);
    T::seed(fn () => $shirt->valueOf('color')->update(['family' => 'white']));
});

it('mặc định: thương hiệu, màu, thuộc tính lọc được — đúng thứ tự, nhãn theo ngôn ngữ; facet không có giá trị thì ẩn', function () {
    $filters = $this->getJson('/api/storefront/v1/products')->assertOk()->json('meta.filters');

    expect(array_column($filters, 'key'))->toBe(['brand', 'color', 'size', 'attr:material'])
        ->and(collect($filters[1]['options'])->firstWhere('value', 'black'))->toMatchArray(['label' => 'Đen', 'count' => 1, 'swatch' => '#111827'])
        ->and($filters[3])->toMatchArray(['param' => 'attr.material', 'label' => 'Chất liệu'])
        ->and($filters[3]['options'][0])->toMatchArray(['value' => 'silk', 'label' => 'Lụa']);

    expect(array_column($this->getJson('/api/storefront/v1/products?q=urbanx')->json('meta.filters'), 'key'))->not->toContain('attr:material');
    expect(collect($this->getJson('/api/storefront/v1/products', ['X-Vani-Locale' => 'en'])->json('meta.filters.1.options'))->firstWhere('value', 'black')['label'])->toBe('Black');
});

it('Admin → Catalog → Bộ lọc: đổi thứ tự, tắt, ô màu → storefront theo cấu hình; khoá lạ bị từ chối', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'catalog.view', 'catalog.manage'])->create(), 'staff');
    $this->get('/admin/catalog/filters')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Catalog::Taxonomy/Filters')
        ->where('facets.4.key', 'attr:material')->where('facets.2', fn ($facet) => $facet['key'] === 'price' && $facet['displays'] === ['range'])->where('facets.1.displays', ['list', 'swatch']));

    $this->put('/admin/catalog/filters', ['facets' => [['key' => 'khong-co', 'enabled' => true, 'display' => 'list']]])->assertSessionHasErrors('facets.0.key');
    $this->put('/admin/catalog/filters', ['facets' => [['key' => 'brand', 'enabled' => true, 'display' => 'swatch']]])->assertSessionHasErrors('facets.0.display');
    $this->put('/admin/catalog/filters', ['facets' => [
        ['key' => 'attr:material', 'enabled' => true, 'display' => 'list'],
        ['key' => 'color', 'enabled' => true, 'display' => 'swatch'],
        ['key' => 'brand', 'enabled' => false, 'display' => 'list'],
    ]])->assertSessionHasNoErrors();

    $filters = $this->getJson('/api/storefront/v1/products')->json('meta.filters');
    expect(array_column($filters, 'key'))->toBe(['attr:material', 'color', 'size'])
        ->and($filters[1]['display'])->toBe('swatch');

    $html = $this->get('/tim-kiem')->assertOk()->assertSee('data-filter="color"', false)->assertDontSee('data-filter="brand"', false)->getContent();
    expect($html)->toContain('style="background: #111827"')
        ->and($html)->toContain(e(url('/tim-kiem?attr%5Bmaterial%5D=silk')));
});

it('cần quyền catalog.manage để lưu', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'catalog.view'])->create(), 'staff');

    $this->put('/admin/catalog/filters', ['facets' => []])->assertForbidden();
});

it('link lọc theo thuộc tính lọc đúng và bỏ chọn được (không JS)', function () {
    $this->get('/tim-kiem?attr%5Bmaterial%5D=silk')->assertOk()->assertSee('Đầm lụa đen')->assertDontSee('Áo thun Urbanx')
        ->assertSee('href="'.e(url('/tim-kiem')).'"  aria-current="true"', false);
});

it('lọc theo size: sản phẩm có biến thể đang bán thuộc size đã chọn; nhiều size là OR; facet theo thứ tự size, đếm bỏ qua chính nhóm size', function () {
    // Đầm: S, M — Áo: M, L (cùng một bản ghi size M); thêm size XL chỉ có ở biến thể đã ngừng bán của áo.
    T::seed(function () {
        $dressS = Variant::query()->orderBy('id')->first();
        $shirtM = Variant::query()->orderByDesc('id')->first();
        $dressS->valueOf('size')->update(['code' => 'S', 'position' => 1]);
        $medium = $shirtM->valueOf('size');
        $medium->update(['code' => 'M', 'position' => 2]);
        $large = OptionValue::factory()->size()->create(['code' => 'L', 'position' => 3]);
        $extraLarge = OptionValue::factory()->size()->create(['code' => 'XL', 'position' => 4]);
        T::optionValues($dressS->style, $medium);
        T::optionValues($shirtM->style, $large, $extraLarge);
        $options = app(ProductOptions::class);
        $options->createVariant($dressS->style, [$dressS->valueOf('color')->id, $medium->id], ['sku' => 'DRESS-M']);
        $options->createVariant($shirtM->style, [$shirtM->valueOf('color')->id, $large->id], ['sku' => 'SHIRT-L']);
        $options->createVariant($shirtM->style, [$shirtM->valueOf('color')->id, $extraLarge->id], ['sku' => 'SHIRT-XL', 'status' => 'inactive']);
    });
    $list = fn (string $query = '') => $this->getJson('/api/storefront/v1/products'.$query)->assertOk();
    $slugs = fn ($response) => collect($response->json('data'))->pluck('slug')->sort()->values()->all();

    $sizes = collect($list()->json('meta.filters'))->firstWhere('key', 'size');
    expect($sizes)->toMatchArray(['param' => 'size', 'label' => 'Kích cỡ', 'display' => 'list'])
        ->and($sizes['options'])->toBe([
            ['value' => 'S', 'label' => 'S', 'count' => 1], ['value' => 'M', 'label' => 'M', 'count' => 2], ['value' => 'L', 'label' => 'L', 'count' => 1],
        ]);

    expect($slugs($list('?size=S')))->toBe(['dam-lua-den'])
        ->and($slugs($list('?size=S,L')))->toBe(['ao-thun-urbanx', 'dam-lua-den'])
        ->and($slugs($list('?size=XL')))->toBe([])
        // AND với nhóm khác; facet size vẫn hiện mọi size của kết quả theo các nhóm còn lại.
        ->and($slugs($list('?size=M&brand=urbanx')))->toBe(['ao-thun-urbanx'])
        ->and(collect(collect($list('?size=S&brand=urbanx')->json('meta.filters'))->firstWhere('key', 'size')['options'])->pluck('value')->all())->toBe(['M', 'L']);

    $this->get('/tim-kiem?size=S')->assertOk()->assertSee('data-filter="size"', false)->assertSee('Đầm lụa đen')->assertDontSee('Áo thun Urbanx');
});

it('lọc và sắp xếp theo giá niêm yết: khoảng giá của kết quả, price_min/price_max, giá đổi thì thứ tự đổi theo', function () {
    // Áo vào bảng giá `base` qua luồng thật (PriceChanged → đồng bộ sang Catalog); đầm đã có sẵn → lệnh đồng bộ điền nốt.
    $base = PriceList::query()->where('code', 'base')->firstOrFail();
    $shirt = Variant::query()->orderByDesc('id')->firstOrFail();
    $dress = Variant::query()->orderBy('id')->firstOrFail();
    T::seed(fn () => app(PriceListService::class)->setPrices($base, [['variant_id' => $shirt->id, 'amount' => 150_000, 'compare_at_amount' => null]]));
    $this->artisan('vani:pricing:sync-list-prices')->assertSuccessful();
    $list = fn (string $query = '') => $this->getJson('/api/storefront/v1/products'.$query)->assertOk();
    $slugs = fn ($response) => collect($response->json('data'))->pluck('slug')->all();

    $price = collect($list()->json('meta.filters'))->firstWhere('key', 'price');
    expect($price)->toMatchArray(['param' => 'price', 'label' => 'Giá', 'display' => 'range', 'options' => [], 'range' => ['min' => 150_000, 'max' => 300_000]])
        ->and($slugs($list('?price_max=200000')))->toBe(['ao-thun-urbanx'])
        ->and($slugs($list('?price_min=200000')))->toBe(['dam-lua-den'])
        ->and($slugs($list('?price_min=100000&price_max=400000&sort=price_asc')))->toBe(['ao-thun-urbanx', 'dam-lua-den'])
        ->and($slugs($list('?sort=price_desc')))->toBe(['dam-lua-den', 'ao-thun-urbanx'])
        // Khoảng giá gợi ý không co lại theo chính bộ lọc giá.
        ->and(collect($list('?price_max=200000')->json('meta.filters'))->firstWhere('key', 'price')['range'])->toBe(['min' => 150_000, 'max' => 300_000]);
    $this->getJson('/api/storefront/v1/products?sort=gia')->assertStatus(422);
    $this->getJson('/api/storefront/v1/products?price_min=-1')->assertStatus(422);

    // Nhân viên hạ giá đầm → rẻ nhất, không cần chạy lệnh.
    T::seed(fn () => app(PriceListService::class)->setPrices($base, [['variant_id' => $dress->id, 'amount' => 90_000, 'compare_at_amount' => null]]));
    expect($slugs($list('?sort=price_asc')))->toBe(['dam-lua-den', 'ao-thun-urbanx']);

    $this->get('/tim-kiem?price_min=100000')->assertOk()->assertSee('data-filter="price"', false)->assertSee('name="price_min"', false)
        ->assertSee('<option value="price_asc"', false)->assertSee('Áo thun Urbanx')->assertDontSee('Đầm lụa đen');
});

it('ẩn giá với khách chưa đăng nhập: không lọc, không sắp xếp, không trả khoảng giá', function () {
    $base = PriceList::query()->where('code', 'base')->firstOrFail();
    T::seed(fn () => app(PriceListService::class)->setPrices($base, [['variant_id' => Variant::query()->orderByDesc('id')->firstOrFail()->id, 'amount' => 150_000, 'compare_at_amount' => null]]));
    $this->artisan('vani:pricing:sync-list-prices')->assertSuccessful();
    T::seed(fn () => app(Settings::class)->set('core', 'storefront.price_visibility', 'members'));

    $response = $this->getJson('/api/storefront/v1/products?price_max=200000&sort=price_asc')->assertOk();
    expect($response->json('data'))->toHaveCount(2)
        ->and(collect($response->json('data'))->pluck('slug')->all())->toBe(['ao-thun-urbanx', 'dam-lua-den'])
        ->and(array_column($response->json('meta.filters'), 'key'))->not->toContain('price')
        ->and($response->json('meta.facets'))->not->toHaveKey('price');
    $this->get('/tim-kiem')->assertOk()->assertDontSee('data-filter="price"', false)->assertDontSee('<option value="price_asc"', false);
});
