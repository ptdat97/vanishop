<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create();
    $this->actingAs(T::staff(), 'staff');
    T::product($this->brand->id, ['style_code' => 'LM01']);
    T::product($this->brand->id, ['style_code' => 'LM02']);
    T::product($this->other->id, ['style_code' => 'UX01']);
});

function collectionPayload(string $codes): array
{
    return ['slug' => 'he-2026', 'status' => 'active', 'position' => 0, 'translations' => ['vi' => ['name' => 'Hè 2026']], 'style_codes' => $codes];
}

it('tạo bộ sưu tập theo danh sách mã, giữ thứ tự', function () {
    $this->post('/admin/catalog/collections', collectionPayload("LM02\nLM01"))->assertSessionHasNoErrors();

    $collection = T::seed(fn () => ProductCollection::query()->sole());
    expect(T::seed(fn () => $collection->styles()->pluck('style_code')->all()))->toBe(['LM02', 'LM01']);

    $this->get("/admin/catalog/collections/{$collection->id}/edit")
        ->assertInertia(fn (Assert $page) => $page->component('Catalog::Collections/Form')->where('collection.style_codes', "LM02\nLM01"));
});

it('báo mã sản phẩm không tồn tại; sản phẩm của brand khác vẫn thêm được (một catalog chung)', function () {
    $this->post('/admin/catalog/collections', collectionPayload('LM01, UX01, KHONGCO'))
        ->assertSessionHasErrors(['style_codes' => __('catalog::messages.style_codes_not_found', ['codes' => 'KHONGCO'])]);
    $this->post('/admin/catalog/collections', collectionPayload('LM01, UX01'))->assertSessionHasNoErrors();
});

it('CollectionDirectory trả slug bộ sưu tập đang hiển thị theo style', function () {
    $this->post('/admin/catalog/collections', collectionPayload('LM01'))->assertSessionHasNoErrors();
    $this->post('/admin/catalog/collections', array_replace(collectionPayload('LM01'), ['slug' => 'sale-2026', 'status' => 'hidden']))->assertSessionHasNoErrors();

    $lm01 = T::seed(fn () => Style::where('style_code', 'LM01')->value('id'));
    $ux01 = T::seed(fn () => Style::where('style_code', 'UX01')->value('id'));
    expect(app(CollectionDirectory::class)->slugsForStyles([$lm01, $ux01]))->toBe([$lm01 => ['he-2026']])
        ->and(app(CollectionDirectory::class)->slugsForStyles([]))->toBe([]);
});

it('danh sách bộ sưu tập', function () {
    $this->post('/admin/catalog/collections', collectionPayload('LM01'));

    $this->get('/admin/catalog/collections')
        ->assertInertia(fn (Assert $page) => $page->component('Catalog::Collections/Index')->where('collections.0.styles_count', 1));
});

it('cùng khuôn với danh mục: ảnh bìa từ Thư viện ảnh, SEO theo ngôn ngữ, chống ghi đè khi hai người cùng sửa', function () {
    $this->post('/admin/catalog/collections', collectionPayload('LM01'))->assertSessionHasNoErrors();
    $collection = T::seed(fn () => ProductCollection::query()->sole());
    $media = T::seed(fn () => Media::query()->create(['disk' => 'public', 'path' => 'media/bia.jpg', 'original_name' => 'bia.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 10, 'width' => 1600, 'height' => 900, 'checksum' => str_repeat('a', 64)]));
    $edit = fn (array $overrides) => array_replace_recursive(collectionPayload('LM01'), $overrides);

    $this->put("/admin/catalog/collections/{$collection->id}", $edit(['lock_version' => 0, 'image_media_id' => $media->id, 'translations' => ['vi' => ['meta_title' => 'Hè rực rỡ']]]))->assertSessionHasNoErrors();
    $this->put("/admin/catalog/collections/{$collection->id}", $edit(['lock_version' => 0]))->assertSessionHasErrors('lock_version');
    $this->put("/admin/catalog/collections/{$collection->id}", $edit(['lock_version' => 1, 'image_media_id' => 999_999]))->assertSessionHasErrors('image_media_id');

    $this->get("/admin/catalog/collections/{$collection->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('collection.image_media_id', $media->id)->where('collection.lock_version', 1)
        ->where('collection.translations.vi.meta_title', 'Hè rực rỡ')->where('extensionSections', [])->where('extensionLinks', []));
    expect(T::seed(fn () => Mediable::query()->where(['mediable_type' => 'collection', 'mediable_id' => $collection->id])->pluck('media_id')->all()))->toBe([$media->id]);
});
