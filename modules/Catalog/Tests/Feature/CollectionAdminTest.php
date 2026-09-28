<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create();
    $this->actingAs(T::staffFor($this->brand), 'staff');
    T::product($this->brand->id, ['style_code' => 'LM01']);
    T::product($this->brand->id, ['style_code' => 'LM02']);
    T::product($this->other->id, ['style_code' => 'UX01']);
});

function collectionPayload(string $codes): array
{
    return ['slug' => 'he-2026', 'status' => 'active', 'position' => 0, 'translations' => ['vi' => ['name' => 'Hè 2026']], 'style_codes' => $codes];
}

it('tạo bộ sưu tập theo danh sách mã, giữ thứ tự', function () {
    $this->post('/admin/catalog/lumiere/collections', collectionPayload("LM02\nLM01"))->assertSessionHasNoErrors();

    $collection = T::seed(fn () => ProductCollection::query()->sole());
    expect(T::seed(fn () => $collection->styles()->pluck('style_code')->all()))->toBe(['LM02', 'LM01']);

    $this->get("/admin/catalog/lumiere/collections/{$collection->id}/edit")
        ->assertInertia(fn (Assert $page) => $page->component('Catalog::Collections/Form')->where('collection.style_codes', "LM02\nLM01"));
});

it('báo mã không tồn tại, kể cả mã của brand khác', function () {
    $this->post('/admin/catalog/lumiere/collections', collectionPayload('LM01, UX01, KHONGCO'))
        ->assertSessionHasErrors(['style_codes' => __('catalog::messages.style_codes_not_found', ['codes' => 'UX01, KHONGCO'])]);
});

it('danh sách bộ sưu tập', function () {
    $this->post('/admin/catalog/lumiere/collections', collectionPayload('LM01'));

    $this->get('/admin/catalog/lumiere/collections')
        ->assertInertia(fn (Assert $page) => $page->component('Catalog::Collections/Index')->where('collections.0.styles_count', 1));
});
