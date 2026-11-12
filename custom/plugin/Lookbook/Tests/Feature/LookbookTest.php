<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Contracts\MediaUrls;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Testing\ImageSizeProviderContract;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract as Api;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Testing\StorefrontBlockContract;
use Plugin\Lookbook\Infrastructure\LatestLookbooksBlock;
use Plugin\Lookbook\Infrastructure\LookbookImageSizes;
use Plugin\Lookbook\Infrastructure\Lookbooks;
use Plugin\Lookbook\LookbookServiceProvider;
use Plugin\Lookbook\Persistence\Lookbook;

ImageSizeProviderContract::define('vani.lookbook', fn () => app(LookbookImageSizes::class));
StorefrontBlockContract::define('vani.lookbook latest', fn () => app(LatestLookbooksBlock::class), config: ['title' => 'Lookbook', 'limit' => 3]);

function lookbookMedia(string $name = 'anh.jpg'): Media
{
    $name = uniqid().'-'.$name;
    Storage::disk('public')->put("media/{$name}", 'x');

    return Media::query()->create(['disk' => 'public', 'path' => "media/{$name}", 'folder' => '', 'original_name' => $name, 'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'width' => 1200, 'height' => 1600, 'checksum' => hash('sha256', $name)]);
}

beforeEach(function () {
    Storage::fake('public');
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(LookbookServiceProvider::ID);
        app(PluginManager::class)->enable(LookbookServiceProvider::ID);
    });
    app()->register(LookbookServiceProvider::class);
    Cache::flush();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'lookbook.view', 'lookbook.manage', 'media.view'])->create(), 'staff');
    $this->admin = '/admin/plugins/vani-lookbook';
    $this->category = T::seed(function () {
        $category = Category::factory()->create(['slug' => 'dam']);
        $category->syncTranslations(['vi' => ['name' => 'Đầm']]);

        return $category;
    });
    $this->save = fn (array $overrides = []) => app(Lookbooks::class)->save(null, array_replace_recursive([
        'translations' => ['vi' => ['name' => 'Thu Đông 2026', 'description' => "Bộ sưu tập **mùa thu**.\n\n<script>x</script>"], 'en' => ['name' => 'Autumn Winter 2026']],
        'images' => [['media_id' => lookbookMedia('a.jpg')->id, 'alt' => ['vi' => 'Áo khoác']], ['media_id' => lookbookMedia('b.jpg')->id]],
        'category_id' => $this->category->id, 'active' => true,
    ], $overrides));
});

it('Admin: tạo lookbook (slug tự sinh từ tên, trùng thì báo lỗi), danh sách, sửa, xoá bỏ khai báo ảnh', function () {
    $media = lookbookMedia('c.jpg');
    $this->post($this->admin, ['translations' => ['vi' => ['name' => '']]])->assertSessionHasErrors('translations.vi.name');
    $this->post($this->admin, ['translations' => ['vi' => ['name' => 'Hè rực rỡ']], 'images' => [['media_id' => $media->id]], 'active' => true])->assertSessionHasNoErrors();
    $lookbook = Lookbook::query()->sole();
    expect($lookbook->slug)->toBe('he-ruc-ro')
        ->and(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeTrue();

    $this->post($this->admin, ['translations' => ['vi' => ['name' => 'Hè rực rỡ']]])->assertSessionHasErrors('slug');
    $this->get("{$this->admin}?per_page=50")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Lookbook::Index')
        ->where('lookbooks.data.0.images_count', 1)->where('lookbooks.per_page', 50));
    $this->get("{$this->admin}?per_page=101")->assertSessionHasErrors('per_page');
    $this->get("{$this->admin}/{$lookbook->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Lookbook::Form')->where('lookbook.images.0.media_id', $media->id));

    $this->delete("{$this->admin}/{$lookbook->id}")->assertRedirect();
    expect(Lookbook::query()->count())->toBe(0)
        ->and(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeFalse();
});

it('storefront: danh sách + chi tiết (ảnh theo thứ tự, Markdown an toàn, "Xem thêm tại"), header, sitemap; lookbook tắt → 404', function () {
    $lookbook = ($this->save)();
    $hidden = ($this->save)(['slug' => 'nhap', 'active' => false, 'translations' => ['vi' => ['name' => 'Nháp']]]);

    $this->get('/lookbook')->assertOk()->assertSee('Thu Đông 2026')->assertDontSee('Nháp')->assertSee('data-lookbook-nav', false);
    $html = $this->get('/lookbook/'.$lookbook->slug)->assertOk()->assertSee('<strong>mùa thu</strong>', false)->assertDontSee('<script>x', false)
        ->assertSee('data-lookbook-category', false)->assertSee('alt="Áo khoác"', false)->getContent();
    expect(strpos($html, '-a.jpg'))->toBeLessThan(strpos($html, '-b.jpg'));
    $this->get('/lookbook/'.$hidden->slug)->assertNotFound();

    $this->get('/sitemap.xml')->assertSee(url('/lookbook/'.$lookbook->slug), false)->assertDontSee('/lookbook/nhap', false);
    Api::assertRoutesDocumented(dirname(__DIR__, 2));
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-lookbook/lookbooks'), dirname(__DIR__, 2), '/lookbooks', 'GET')->assertOk()->assertJsonCount(1, 'data');
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-lookbook/lookbooks/'.$lookbook->slug), dirname(__DIR__, 2), '/lookbooks/{slug}', 'GET')->assertOk()->assertJsonCount(2, 'data.images');
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-lookbook/lookbooks/'.$hidden->slug), dirname(__DIR__, 2), '/lookbooks/{slug}', 'GET')->assertNotFound();
});

it('bản tiếng Anh dùng tên đã dịch; thiếu bản dịch thì hiện tiếng Việt; danh mục bị ẩn → không có link', function () {
    $lookbook = ($this->save)();
    $this->get('/en/lookbook/'.$lookbook->slug)->assertOk()->assertSee('Autumn Winter 2026')->assertSee('mùa thu');

    T::seed(fn () => $this->category->update(['status' => 'hidden']));
    $this->get('/lookbook/'.$lookbook->slug)->assertOk()->assertDontSee('data-lookbook-category', false);
});

it('không có lookbook đang bật → không có link header, sitemap không có trang lookbook', function () {
    ($this->save)(['active' => false]);

    $this->get('/')->assertOk()->assertDontSee('data-lookbook-nav', false);
    $this->get('/sitemap.xml')->assertDontSee('/lookbook', false);
});

it('ảnh bìa ở lưới dùng kích thước riêng của plugin (lookbook.cover 600×900 cắt khung), nhân viên ghi đè được', function () {
    $cover = lookbookMedia('cover.jpg');
    ($this->save)(['cover_media_id' => $cover->id]);
    $sizes = app(ImageSizes::class);

    expect(collect($sizes->catalog())->firstWhere('name', LookbookImageSizes::COVER))->toMatchArray(['plugin' => 'vani.lookbook', 'overridden' => false])
        ->and($this->get('/lookbook')->assertOk()->getContent())->toContain($cover->checksum.'-w600h900c.jpg');

    $sizes->save(['widths' => [400, 800], 'product_ratio' => '2:3', 'quality' => 80, 'overrides' => [LookbookImageSizes::COVER => ['width' => 500, 'height' => 500, 'crop' => true]]]);
    app()->forgetInstance(ImageCache::class);
    app()->forgetInstance(MediaUrls::class);
    expect($this->get('/lookbook')->getContent())->toContain($cover->checksum.'-w500h500c.jpg')
        ->and(collect($sizes->catalog())->firstWhere('name', LookbookImageSizes::COVER)['overridden'])->toBeTrue();
});
