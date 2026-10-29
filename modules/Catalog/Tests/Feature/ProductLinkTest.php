<?php

use Modules\Catalog\Persistence\Models\LinkType;
use Modules\Catalog\Persistence\Models\StyleLinkGroup;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->actingAs(T::staff(), 'staff');
    $this->similar = T::seed(fn () => LinkType::factory()->create(['code' => 'similar', 'position' => 2]));
    $this->similar->syncTranslations(['vi' => ['name' => 'Sản phẩm tương tự']]);
    $this->crossSell = T::seed(fn () => LinkType::factory()->create(['code' => 'cross-sell', 'position' => 1]));

    $this->shirt = T::product(null, ['style_code' => 'SHIRT', 'slug' => 'ao']);
    $this->pants = T::product(null, ['style_code' => 'PANTS', 'slug' => 'quan']);
    $this->skirt = T::product(null, ['style_code' => 'SKIRT', 'slug' => 'vay']);
    $this->draft = T::product(null, ['style_code' => 'DRAFT', 'slug' => 'nhap', 'status' => 'draft']);
    $this->url = fn ($style) => "/admin/catalog/products/{$style->id}/links";
});

function linkedSlugs(string $slug, string $code): array
{
    $links = collect(test()->getJson("/api/storefront/v1/products/{$slug}")->assertOk()->json('data.links'));

    return collect($links->firstWhere('code', $code)['products'] ?? [])->pluck('slug')->all();
}

it('liên kết hai chiều: mọi sản phẩm trong nhóm thấy nhau trên PDP', function () {
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS', 'SKIRT']])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Đã liên kết thêm 2 sản phẩm.');

    expect(linkedSlugs('ao', 'similar'))->toBe(['quan', 'vay'])
        ->and(linkedSlugs('quan', 'similar'))->toBe(['ao', 'vay'])
        ->and(linkedSlugs('vay', 'similar'))->toBe(['ao', 'quan']);
    $this->getJson('/api/storefront/v1/products/ao')->assertJsonPath('data.links.0.name', 'Sản phẩm tương tự');
});

it('liên kết một chiều: chỉ sản phẩm gốc thấy các sản phẩm được gợi ý', function () {
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->crossSell->id, 'style_codes' => ['PANTS'], 'one_way' => true])->assertSessionHasNoErrors();

    expect(linkedSlugs('ao', 'cross-sell'))->toBe(['quan'])
        ->and(linkedSlugs('quan', 'cross-sell'))->toBe([]);
});

it('PDP xếp loại theo thứ tự, bỏ sản phẩm không hiển thị và loại đang tắt', function () {
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS', 'DRAFT']]);
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->crossSell->id, 'style_codes' => ['DRAFT'], 'one_way' => true]);

    // cross-sell chỉ có sản phẩm nháp → cả loại bị bỏ.
    $this->getJson('/api/storefront/v1/products/ao')
        ->assertJsonCount(1, 'data.links')
        ->assertJsonPath('data.links.0.code', 'similar')
        ->assertJsonPath('data.links.0.products.0.slug', 'quan')
        ->assertJsonCount(1, 'data.links.0.products');

    T::seed(fn () => $this->similar->update(['is_active' => false]));
    $this->getJson('/api/storefront/v1/products/ao')->assertJsonPath('data.links', []);
});

it('gỡ liên kết khỏi nhóm; nhóm chỉ còn một sản phẩm thì bị xoá', function () {
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS']]);

    $this->delete(($this->url)($this->shirt)."/{$this->similar->id}/{$this->pants->id}")->assertSessionHasNoErrors();

    expect(linkedSlugs('ao', 'similar'))->toBe([])
        ->and(T::seed(fn () => StyleLinkGroup::query()->count()))->toBe(0);
});

it('gỡ liên kết từ sản phẩm thành viên không đụng nhóm một chiều của sản phẩm gốc', function () {
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->crossSell->id, 'style_codes' => ['PANTS'], 'one_way' => true]);

    $this->delete(($this->url)($this->pants)."/{$this->crossSell->id}/{$this->shirt->id}");

    expect(linkedSlugs('ao', 'cross-sell'))->toBe(['quan']);
});

it('báo mã sản phẩm không tồn tại và bỏ qua chính nó, liên kết đã có', function () {
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS', 'KHONG-CO']])
        ->assertSessionHasErrors(['style_codes' => 'Không tìm thấy mã sản phẩm: KHONG-CO']);

    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS']]);
    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS', 'SHIRT']])
        ->assertSessionHas('success', 'Đã liên kết thêm 0 sản phẩm.');
    expect(linkedSlugs('ao', 'similar'))->toBe(['quan']);
});

it('cần quyền catalog.manage', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');

    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS']])->assertForbidden();
});

it('quản lý loại liên kết; không xoá loại đang được dùng', function () {
    $this->post('/admin/catalog/link-types', ['code' => 'upsell', 'is_active' => true, 'position' => 3, 'translations' => ['vi' => ['name' => 'Nâng cấp']]])
        ->assertSessionHasNoErrors();
    expect(T::seed(fn () => LinkType::query()->where('code', 'upsell')->sole()->translate('name')))->toBe('Nâng cấp');

    $this->post('/admin/catalog/link-types', ['code' => 'Có Dấu', 'is_active' => true, 'position' => 0, 'translations' => ['vi' => ['name' => 'X']]])
        ->assertSessionHasErrors('code');

    $this->post(($this->url)($this->shirt), ['link_type_id' => $this->similar->id, 'style_codes' => ['PANTS']]);
    $this->delete("/admin/catalog/link-types/{$this->similar->id}")
        ->assertSessionHasErrors(['link_type' => 'Loại liên kết còn sản phẩm đang dùng, hãy tắt thay vì xoá.']);
    $this->delete("/admin/catalog/link-types/{$this->crossSell->id}")->assertSessionHasNoErrors();
    expect(T::seed(fn () => LinkType::query()->pluck('code')->sort()->values()->all()))->toBe(['similar', 'upsell']);
});
