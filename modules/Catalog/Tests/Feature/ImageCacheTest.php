<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\MediaLibrary;

beforeEach(function () {
    Storage::fake('public');
    $this->cacheDir = storage_path('framework/testing/image-cache-'.bin2hex(random_bytes(4)));
    config(['vanishop.media.cache.path' => $this->cacheDir, 'vanishop.media.cache.widths' => [400, 800, 1600]]);
    app()->forgetInstance(ImageCache::class);
    $this->library = app(MediaLibrary::class);
});

afterEach(fn () => File::deleteDirectory($this->cacheDir));

it('url(width): bản nhỏ nhất rộng ≥ width trong /cache, giữ định dạng gốc; không phóng to → ảnh gốc', function () {
    $large = $this->library->store(UploadedFile::fake()->image('dam.jpg', 1200, 1500));
    $small = $this->library->store(UploadedFile::fake()->image('logo.png', 300, 100));
    $prefix = asset('cache/media/'.substr($large->checksum, 0, 2).'/'.$large->checksum);

    expect($large->url(400))->toBe("{$prefix}-w400.jpg")
        ->and($large->url(500))->toBe("{$prefix}-w800.jpg")
        ->and($large->url(1600))->toBe($large->url())
        ->and($large->url())->toBe(Storage::disk('public')->url($large->path))
        ->and($small->url(400))->toBe($small->url())
        ->and(File::exists($this->cacheDir))->toBeFalse();
});

it('request đầu tiên tạo file cache đúng kích thước rồi trả về; lần sau dùng lại file', function () {
    $media = $this->library->store(UploadedFile::fake()->image('dam.png', 1200, 1500));
    $url = parse_url($media->url(400), PHP_URL_PATH);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');

    $file = app(ImageCache::class)->path($media->checksum, 400, 'png');
    expect(getimagesize($file))->toMatchArray([0 => 400, 1 => 500, 'mime' => 'image/png']);

    $modified = filemtime($file);
    $this->travel(5)->seconds();
    $this->get($url)->assertOk();
    clearstatcache();
    expect(filemtime($file))->toBe($modified);
});

it('chỉ tạo đúng chiều rộng/đuôi file của cấu hình: kích thước tuỳ ý, phóng to, sai đuôi, ảnh lạ → 404', function () {
    $media = $this->library->store(UploadedFile::fake()->image('dam.jpg', 1000, 1200));
    $base = '/cache/media/'.substr($media->checksum, 0, 2).'/'.$media->checksum;

    $this->get("{$base}-w500.jpg")->assertNotFound();
    $this->get("{$base}-w1600.jpg")->assertNotFound();
    $this->get("{$base}-w400.webp")->assertNotFound();
    $this->get('/cache/media/ff/'.str_repeat('f', 64).'-w400.jpg')->assertNotFound();
    $this->get('/cache/media/00/'.$media->checksum.'-w400.jpg')->assertNotFound();

    expect(File::exists($this->cacheDir.'/media'))->toBeFalse();
});

it('ảnh hỏng không giải mã được → chuyển về ảnh gốc, không tạo file', function () {
    $media = $this->library->store(UploadedFile::fake()->image('dam.jpg', 1000, 1200));
    Storage::disk('public')->put($media->path, 'không phải ảnh');

    $this->get(parse_url($media->url(400), PHP_URL_PATH))->assertRedirect($media->url());

    expect(File::exists(app(ImageCache::class)->path($media->checksum, 400, 'jpg')))->toBeFalse();
});

it('vani:media:cache --warm tạo trước mọi bản, --clear xoá hết', function () {
    $media = $this->library->store(UploadedFile::fake()->image('dam.jpg', 1000, 1200));
    $cache = app(ImageCache::class);

    $this->artisan('vani:media:cache --warm')->expectsOutputToContain('Đã có 2 bản thu nhỏ')->assertSuccessful();
    expect(File::exists($cache->path($media->checksum, 400, 'jpg')))->toBeTrue()
        ->and(File::exists($cache->path($media->checksum, 800, 'jpg')))->toBeTrue();

    $this->artisan('vani:media:cache --clear')->assertSuccessful();
    expect(File::exists($cache->path($media->checksum, 400, 'jpg')))->toBeFalse();

    $this->artisan('vani:media:cache')->assertFailed();
});
