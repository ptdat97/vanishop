<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

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
    T::seed(fn () => $variant->styleColor->color->update(['color_family' => 'black']));
    P::priceList(['code' => 'base'], [$variant->id => [300_000]]);
    [$shirt] = P::variants(T::product($this->urbanx->id, ['name' => 'Áo thun Urbanx', 'slug' => 'ao-thun-urbanx']), ['M']);
    P::priceList(['code' => 'base-2'], [$shirt->id => [150_000]]);
    T::seed(fn () => $shirt->styleColor->color->update(['color_family' => 'white']));
});

it('mặc định: thương hiệu, màu, thuộc tính lọc được — đúng thứ tự, nhãn theo ngôn ngữ; facet không có giá trị thì ẩn', function () {
    $filters = $this->getJson('/api/storefront/v1/products')->assertOk()->json('meta.filters');

    expect(array_column($filters, 'key'))->toBe(['brand', 'color', 'attr:material'])
        ->and(collect($filters[1]['options'])->firstWhere('value', 'black'))->toMatchArray(['label' => 'Đen', 'count' => 1, 'swatch' => '#111827'])
        ->and($filters[2])->toMatchArray(['param' => 'attr.material', 'label' => 'Chất liệu'])
        ->and($filters[2]['options'][0])->toMatchArray(['value' => 'silk', 'label' => 'Lụa']);

    expect(array_column($this->getJson('/api/storefront/v1/products?q=urbanx')->json('meta.filters'), 'key'))->not->toContain('attr:material');
    expect(collect($this->getJson('/api/storefront/v1/products', ['X-Vani-Locale' => 'en'])->json('meta.filters.1.options'))->firstWhere('value', 'black')['label'])->toBe('Black');
});

it('Admin → Catalog → Bộ lọc: đổi thứ tự, tắt, ô màu → storefront theo cấu hình; khoá lạ bị từ chối', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'catalog.view', 'catalog.manage'])->create(), 'staff');
    $this->get('/admin/catalog/filters')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Catalog::Taxonomy/Filters')
        ->where('facets.2.key', 'attr:material')->where('facets.1.displays', ['list', 'swatch']));

    $this->put('/admin/catalog/filters', ['facets' => [['key' => 'khong-co', 'enabled' => true, 'display' => 'list']]])->assertSessionHasErrors('facets.0.key');
    $this->put('/admin/catalog/filters', ['facets' => [['key' => 'brand', 'enabled' => true, 'display' => 'swatch']]])->assertSessionHasErrors('facets.0.display');
    $this->put('/admin/catalog/filters', ['facets' => [
        ['key' => 'attr:material', 'enabled' => true, 'display' => 'list'],
        ['key' => 'color', 'enabled' => true, 'display' => 'swatch'],
        ['key' => 'brand', 'enabled' => false, 'display' => 'list'],
    ]])->assertSessionHasNoErrors();

    $filters = $this->getJson('/api/storefront/v1/products')->json('meta.filters');
    expect(array_column($filters, 'key'))->toBe(['attr:material', 'color'])
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
