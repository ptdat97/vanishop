<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\EloquentMediaUrls;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Pricing\Tests\Feature\PricingTestHelpers;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

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
    $cached = app(ImageCache::class)->path($unusedMedia->checksum, 'w400', 'jpg');
    expect(File::exists($cached))->toBeTrue();

    $this->postJson("{$this->base}/items/delete", ['ids' => [$used['id'], $unused['id']]])->assertOk()->assertJsonPath('deleted', 1)->assertJsonPath('in_use', 1);

    expect(Media::query()->find($unused['id']))->toBeNull()
        ->and(Media::query()->find($used['id']))->not->toBeNull()
        ->and(File::exists($cached))->toBeFalse();
    Storage::disk('public')->assertMissing($unusedMedia->path);
    $this->getJson("{$this->base}/browse")->assertJsonPath('items.0.usages_count', 1);
});

it('chọn từ thư viện: gắn ảnh có sẵn vào SKU (bỏ qua ảnh đã có) và làm ảnh danh mục', function () {
    $items = uploadTo('', [UploadedFile::fake()->image('a.jpg', 900, 1200), UploadedFile::fake()->image('b.jpg', 900, 1201)]);
    $ids = array_column($items, 'id');
    $style = T::product();
    [$variant] = PricingTestHelpers::variants($style, ['S']);
    $url = "/admin/catalog/products/{$style->id}/variants/{$variant->id}/images/library";

    $this->post($url, ['media_ids' => [$ids[0]]])->assertSessionHasNoErrors();
    $this->post($url, ['media_ids' => $ids])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $variant->gallery()->pluck('media_id')->all()))->toBe($ids);

    $this->post($url, ['media_ids' => [999_999]])->assertSessionHasErrors('images');

    $category = T::seed(fn () => Category::factory()->create());
    $this->post("/admin/catalog/categories/{$category->id}/image/library", ['media_id' => $ids[1], 'alt' => 'Đầm hè'])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $category->media()->sole()))->media_id->toBe($ids[1])->alt->toBe('Đầm hè');
    $this->post("/admin/catalog/categories/{$category->id}/image/library", ['media_id' => 999_999])->assertSessionHasErrors('image');

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $this->post($url, ['media_ids' => $ids])->assertForbidden();
});

it('gán một bộ ảnh cho nhiều SKU cùng lúc: giữ thứ tự đã chọn, bỏ ảnh SKU đã có; vượt giới hạn hay SKU lạ thì không gán gì', function () {
    $ids = array_column(uploadTo('', [UploadedFile::fake()->image('a.jpg', 900, 1200), UploadedFile::fake()->image('b.jpg', 900, 1201)]), 'id');
    $style = T::product();
    [$small, $medium, $large] = PricingTestHelpers::variants($style, ['S', 'M', 'L']);
    $url = "/admin/catalog/products/{$style->id}/images/assign";
    $gallery = fn ($variant): array => T::seed(fn () => $variant->gallery()->pluck('media_id')->all());

    $this->post("/admin/catalog/products/{$style->id}/variants/{$small->id}/images/library", ['media_ids' => [$ids[1]]])->assertSessionHasNoErrors();
    $this->post($url, ['media_ids' => [$ids[1], $ids[0]], 'variant_ids' => [$small->id, $medium->id]])->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($gallery($small))->toBe([$ids[1], $ids[0]])
        ->and($gallery($medium))->toBe([$ids[1], $ids[0]])
        ->and($gallery($large))->toBe([]);

    $foreign = PricingTestHelpers::variants(T::product(), ['XL'])[0];
    $this->post($url, ['media_ids' => [$ids[0]], 'variant_ids' => [$large->id, $foreign->id]])->assertSessionHasErrors('variant_ids');
    expect($gallery($large))->toBe([]);

    // SKU đầu (S) gán được, SKU sau (L) đã đủ 20 ảnh → huỷ cả lượt, S giữ nguyên.
    T::seed(fn () => collect(range(1, 20))->each(fn (int $position) => $large->gallery()->create(['media_id' => $ids[1], 'role' => 'gallery', 'position' => $position])));
    $other = array_column(uploadTo('', [UploadedFile::fake()->image('c.jpg', 900, 1202)]), 'id');
    $this->post($url, ['media_ids' => $other, 'variant_ids' => [$small->id, $large->id]])->assertSessionHasErrors('images');
    expect($gallery($small))->toBe([$ids[1], $ids[0]]);

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $this->post($url, ['media_ids' => [$ids[0]], 'variant_ids' => [$medium->id]])->assertForbidden();
});

it('ô chọn ảnh dùng chung: /admin/media/items trả ảnh theo id (giữ thứ tự, bỏ id không còn), cần media.view', function () {
    [$a, $b] = uploadTo('', [UploadedFile::fake()->image('a.jpg', 800, 1200), UploadedFile::fake()->image('b.jpg', 640, 960)]);

    $this->getJson("{$this->base}/items?ids[]={$b['id']}&ids[]=999999&ids[]={$a['id']}")->assertOk()
        ->assertJsonPath('items.0.id', $b['id'])->assertJsonPath('items.1.id', $a['id'])->assertJsonCount(2, 'items');

    $this->actingAs(T::staff(['admin.access']), 'staff');
    $this->getJson("{$this->base}/items?ids[]={$a['id']}")->assertForbidden();
});

it('kiểu trường media: FieldDefinition nhận id ảnh; cấu hình "Ảnh thay thế" là ô chọn ảnh', function () {
    $field = FieldDefinition::media('cover', 'Ảnh bìa', required: true);
    expect($field->rules())->toBe(['required', 'integer', 'min:1'])
        ->and(collect(app(Settings::class)->definitions('core'))->firstWhere('key', EloquentMediaUrls::PLACEHOLDER_SETTING)?->type)->toBe('media');
});

it('logo thương hiệu chọn từ Thư viện ảnh: khai báo đang dùng, storefront dùng bản thu nhỏ; xoá thương hiệu bỏ khai báo', function () {
    [$logo] = uploadTo('', [UploadedFile::fake()->image('logo.png', 900, 300)]);
    $brand = T::seed(fn () => Brand::factory()->create(['slug' => 'lumiere', 'code' => 'LM', 'status' => 'active']));
    $payload = ['code' => 'LM', 'slug' => 'lumiere', 'name' => 'Lumière', 'description' => null, 'status' => 'active', 'position' => 0];

    $this->put("/admin/catalog/brands/{$brand->id}", [...$payload, 'logo_media_id' => 999999])->assertSessionHasErrors('logo_media_id');
    $this->put("/admin/catalog/brands/{$brand->id}", [...$payload, 'logo_media_id' => $logo['id'], 'lock_version' => $brand->fresh()->lock_version])->assertSessionHasNoErrors();

    expect(DB::table('mediables')->where(['media_id' => $logo['id'], 'mediable_type' => 'brand', 'role' => 'logo'])->exists())->toBeTrue()
        ->and($this->getJson('/api/storefront/v1/brands/lumiere')->assertOk()->json('data.logo_url'))->toContain('-w400.');

    $this->delete("/admin/catalog/brands/{$brand->id}")->assertRedirect();
    expect(DB::table('mediables')->where('media_id', $logo['id'])->exists())->toBeFalse();
});

/** MP4 tối thiểu: ftyp + moov/trak/tkhd (version 0) mang kích thước khung $width×$height. */
function fakeMp4(string $name, int $width, int $height): UploadedFile
{
    $box = fn (string $type, string $body): string => pack('N', 8 + strlen($body)).$type.$body;
    $tkhd = "\0\0\0\x03".str_repeat("\0", 20).str_repeat("\0", 8).str_repeat("\0", 8).str_repeat("\0", 36).pack('N', $width * 65536).pack('N', $height * 65536);
    $bytes = $box('ftyp', 'isom'."\0\0\x02\0".'isomiso2mp41').$box('moov', $box('trak', $box('tkhd', $tkhd)));

    return UploadedFile::fake()->createWithContent($name, $bytes.str_repeat("\0", 2048));
}

it('video: tải lên MP4/WebM (giới hạn dung lượng riêng), đọc khung hình MP4, không có bản thu nhỏ; duyệt lọc theo loại', function () {
    [$mp4] = uploadTo('', [fakeMp4('hero.mp4', 1920, 1080)]);
    $webm = UploadedFile::fake()->createWithContent('doc.webm', "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04\x42\xF3\x81\x08\x42\x82\x84webm\x42\x87\x81\x04\x42\x85\x81\x02".str_repeat("\0", 2048));
    [$doc] = uploadTo('', [$webm]);
    [$photo] = uploadTo('', [UploadedFile::fake()->image('anh.jpg', 800, 1200)]);

    expect($mp4)->toMatchArray(['kind' => 'video', 'mime_type' => 'video/mp4', 'width' => 1920, 'height' => 1080, 'thumb_url' => null])
        ->and($doc)->toMatchArray(['kind' => 'video', 'mime_type' => 'video/webm', 'width' => null, 'thumb_url' => null])
        ->and($photo['kind'])->toBe('image')
        ->and(vani_media($mp4['id']))->toBe($mp4['url'])
        ->and(vani_image($mp4['id'], 400))->toBe($mp4['url']);

    $this->getJson("{$this->base}/browse?kind=video&sort=original_name&order=asc")->assertOk()->assertJsonPath('items.*.name', ['doc.webm', 'hero.mp4'])
        ->assertJsonPath('limits.video_kb', 100 * 1024);
    $this->getJson("{$this->base}/browse?kind=image")->assertOk()->assertJsonPath('items.*.id', [$photo['id']]);
    $this->getJson("{$this->base}/browse?kind=pdf")->assertUnprocessable();

    config(['vanishop.media.max_video_kb' => 1]);
    $this->postJson("{$this->base}/upload", ['images' => [fakeMp4('lon.mp4', 640, 360)]])->assertUnprocessable()->assertJsonValidationErrors('images.0');
    $this->postJson("{$this->base}/upload", ['images' => [UploadedFile::fake()->create('clip.mov', 10, 'video/quicktime')]])->assertUnprocessable()->assertJsonValidationErrors('images.0');
});

it('kiểu trường video: FieldDefinition nhận id video của Thư viện ảnh', function () {
    expect(FieldDefinition::video('clip', 'Video', required: true)->rules())->toBe(['required', 'integer', 'min:1'])
        ->and(FieldDefinition::video('clip', 'Video')->type)->toBe('video');
});
