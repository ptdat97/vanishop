<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    Storage::fake('public');
    $this->cacheDir = storage_path('framework/testing/image-cache-'.bin2hex(random_bytes(4)));
    config(['vanishop.media.cache.path' => $this->cacheDir]);
    app()->forgetInstance(ImageCache::class);
    $this->base = '/admin/media';
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'media.view', 'media.manage']), 'staff');
});

afterEach(fn () => File::deleteDirectory($this->cacheDir));

function uploadTo(string $folder, array $files): array
{
    return test()->postJson('/admin/media/upload', ['folder' => $folder, 'images' => $files])->assertOk()->json('items');
}

it('phân quyền: xem/chọn ảnh cần media.view, thao tác ghi cần media.manage', function () {
    $this->actingAs(T::staff(), 'staff');
    $this->get($this->base)->assertForbidden();
    $this->getJson("{$this->base}/browse")->assertForbidden();
    $this->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page->where('urls.media', null));

    $this->actingAs(T::staff(['admin.access', 'media.view']), 'staff');
    $this->get($this->base)->assertInertia(fn (Assert $page) => $page->component('Catalog::Media/Index')->where('canManage', false)->where('urls.media', url($this->base)));
    $this->getJson("{$this->base}/browse")->assertOk()->assertJsonPath('can_manage', false);
    $this->postJson("{$this->base}/upload", ['images' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
    $this->postJson("{$this->base}/folders", ['name' => 'Sản phẩm'])->assertForbidden();
    $this->postJson("{$this->base}/items/delete", ['ids' => [1]])->assertForbidden();
});

it('thư mục ảo: tạo lồng nhau (slug), đổi tên kéo theo thư mục con và ảnh, chỉ xoá thư mục rỗng', function () {
    $this->postJson("{$this->base}/folders", ['name' => 'Sản phẩm'])->assertOk()->assertJsonPath('path', 'san-pham');
    $this->postJson("{$this->base}/folders", ['parent' => 'san-pham', 'name' => 'Áo thun'])->assertOk()->assertJsonPath('path', 'san-pham/ao-thun');
    $this->postJson("{$this->base}/folders", ['name' => 'san pham'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson("{$this->base}/folders", ['parent' => 'khong-co', 'name' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('parent');
    $this->postJson("{$this->base}/folders", ['name' => '!!!'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson("{$this->base}/folders", ['parent' => 'san-pham/ao-thun', 'name' => 'c3'])->assertOk();
    $this->postJson("{$this->base}/folders", ['parent' => 'san-pham/ao-thun/c3', 'name' => 'c4'])->assertOk();
    $this->postJson("{$this->base}/folders", ['parent' => 'san-pham/ao-thun/c3/c4', 'name' => 'c5'])->assertUnprocessable();

    [$image] = uploadTo('san-pham/ao-thun', [UploadedFile::fake()->image('ao-trang.jpg', 800, 1000)]);

    $this->postJson("{$this->base}/folders/rename", ['path' => 'san-pham', 'name' => 'Hàng mới'])->assertOk()->assertJsonPath('path', 'hang-moi');
    $this->getJson("{$this->base}/browse?folder=hang-moi/ao-thun")->assertOk()
        ->assertJsonPath('folders', ['hang-moi', 'hang-moi/ao-thun', 'hang-moi/ao-thun/c3', 'hang-moi/ao-thun/c3/c4'])
        ->assertJsonPath('items.0.id', $image['id'])
        ->assertJsonPath('items.0.folder', 'hang-moi/ao-thun');

    $this->postJson("{$this->base}/folders/delete", ['path' => 'hang-moi'])->assertUnprocessable()->assertJsonValidationErrors('folder');
    $this->postJson("{$this->base}/folders/delete", ['path' => 'hang-moi/ao-thun/c3/c4'])->assertOk();
    $this->postJson("{$this->base}/folders/delete", ['path' => ''])->assertUnprocessable();
});

it('tải lên: chỉ ảnh hợp lệ; ảnh trùng nội dung dùng lại ảnh cũ; duyệt theo thư mục, tìm theo tên mọi thư mục, sắp xếp', function () {
    $this->postJson("{$this->base}/folders", ['name' => 'Banner'])->assertOk();
    $photo = UploadedFile::fake()->image('dam-lua.jpg', 900, 1200);
    [$first] = uploadTo('', [$photo, UploadedFile::fake()->image('ao-so-mi.png', 600, 800)]);
    $again = uploadTo('banner', [$photo]);

    expect($again[0]['id'])->toBe($first['id'])->and($again[0]['folder'])->toBe('')
        ->and($first)->toMatchArray(['name' => 'dam-lua.jpg', 'width' => 900, 'height' => 1200, 'usages_count' => 0]);

    $this->postJson("{$this->base}/upload", ['images' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])->assertUnprocessable()->assertJsonValidationErrors('images.0');
    $this->postJson("{$this->base}/upload", ['folder' => 'khong-co', 'images' => [UploadedFile::fake()->image('a.jpg')]])->assertUnprocessable()->assertJsonValidationErrors('folder');

    $this->getJson("{$this->base}/browse?folder=banner")->assertOk()->assertJsonCount(0, 'items');
    $this->getJson("{$this->base}/browse?sort=original_name&order=asc")->assertOk()->assertJsonPath('items.*.name', ['ao-so-mi.png', 'dam-lua.jpg']);

    uploadTo('banner', [UploadedFile::fake()->image('banner-dam-he.jpg', 1200, 600)]);
    $this->getJson("{$this->base}/browse?keyword=dam")->assertOk()->assertJsonPath('meta.total', 2);
    $this->getJson("{$this->base}/browse?keyword=100%25")->assertOk()->assertJsonPath('meta.total', 0);
});

it('đổi tên, chuyển thư mục; xoá chỉ ảnh không còn dùng (kèm file gốc và bản thu nhỏ)', function () {
    $this->postJson("{$this->base}/folders", ['name' => 'Cũ'])->assertOk();
    [$used, $unused] = uploadTo('', [UploadedFile::fake()->image('dang-dung.jpg', 900, 1200), UploadedFile::fake()->image('bo-di.jpg', 900, 1201)]);

    $this->postJson("{$this->base}/items/{$unused['id']}/rename", ['name' => '  Ảnh bỏ đi  '])->assertOk()->assertJsonPath('item.name', 'Ảnh bỏ đi');
    $this->postJson("{$this->base}/items/move", ['ids' => [$unused['id']], 'folder' => 'cu'])->assertOk();
    $this->postJson("{$this->base}/items/move", ['ids' => [$unused['id']], 'folder' => 'khong-co'])->assertUnprocessable();
    expect(Media::query()->find($unused['id'])->folder)->toBe('cu');

    $category = T::seed(fn () => Category::factory()->create());
    $this->post("/admin/catalog/categories/{$category->id}/image/library", ['media_id' => $used['id']])->assertSessionHasNoErrors();

    $unusedMedia = Media::query()->find($unused['id']);
    $this->get(parse_url($unusedMedia->url(400), PHP_URL_PATH))->assertOk();
    $cached = app(ImageCache::class)->path($unusedMedia->checksum, 400, 'jpg');
    expect(File::exists($cached))->toBeTrue();

    $this->postJson("{$this->base}/items/delete", ['ids' => [$used['id'], $unused['id']]])->assertOk()->assertJsonPath('deleted', 1)->assertJsonPath('in_use', 1);

    expect(Media::query()->find($unused['id']))->toBeNull()
        ->and(Media::query()->find($used['id']))->not->toBeNull()
        ->and(File::exists($cached))->toBeFalse();
    Storage::disk('public')->assertMissing($unusedMedia->path);
    $this->getJson("{$this->base}/browse")->assertJsonPath('items.0.usages_count', 1);
});

it('chọn từ thư viện: gắn ảnh có sẵn vào màu sản phẩm (bỏ qua ảnh đã có) và làm ảnh danh mục', function () {
    $items = uploadTo('', [UploadedFile::fake()->image('a.jpg', 900, 1200), UploadedFile::fake()->image('b.jpg', 900, 1201)]);
    $ids = array_column($items, 'id');
    $style = T::product();
    $styleColor = T::seed(fn () => $style->colors()->create(['color_id' => Color::factory()->create()->id]));
    $url = "/admin/catalog/products/{$style->id}/colors/{$styleColor->id}/images/library";

    $this->post($url, ['media_ids' => [$ids[0]]])->assertSessionHasNoErrors();
    $this->post($url, ['media_ids' => $ids])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $styleColor->gallery()->pluck('media_id')->all()))->toBe($ids);

    $this->post($url, ['media_ids' => [999_999]])->assertSessionHasErrors('images');

    $category = T::seed(fn () => Category::factory()->create());
    $this->post("/admin/catalog/categories/{$category->id}/image/library", ['media_id' => $ids[1], 'alt' => 'Đầm hè'])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $category->media()->sole()))->media_id->toBe($ids[1])->alt->toBe('Đầm hè');
    $this->post("/admin/catalog/categories/{$category->id}/image/library", ['media_id' => 999_999])->assertSessionHasErrors('image');

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $this->post($url, ['media_ids' => $ids])->assertForbidden();
});
