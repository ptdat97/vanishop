<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\MediaWebp\Infrastructure\WebpFormat;
use Plugin\MediaWebp\MediaWebpServiceProvider;

beforeEach(function () {
    Storage::fake('public');
    $this->cacheDir = storage_path('framework/testing/image-cache-'.bin2hex(random_bytes(4)));
    config(['vanishop.media.cache.path' => $this->cacheDir, 'vanishop.media.cache.widths' => [400, 800]]);
    app()->forgetInstance(ImageCache::class);
    $this->library = app(MediaLibrary::class);

    $this->system = fn (Closure $action) => app(CurrentContext::class)->runAs(ContextScope::system('test'), $action);
    ($this->system)(function () {
        app(PluginManager::class)->install(MediaWebpServiceProvider::ID);
        app(PluginManager::class)->enable(MediaWebpServiceProvider::ID);
    });
    app()->register(MediaWebpServiceProvider::class);
    app(PluginActivation::class)->flush();
});

afterEach(fn () => File::deleteDirectory($this->cacheDir));

it('plugin bật: ảnh JPEG/PNG thu nhỏ ra WebP; ảnh gốc giữ nguyên', function () {
    expect(WebpFormat::encoderAvailable())->toBeTrue();
    $jpeg = $this->library->store(UploadedFile::fake()->image('dam.jpg', 1000, 1200));
    $png = $this->library->store(UploadedFile::fake()->image('logo.png', 1000, 500));

    expect($jpeg->url(400))->toEndWith("{$jpeg->checksum}-w400.webp")
        ->and($png->url(400))->toEndWith("{$png->checksum}-w400.webp")
        ->and($jpeg->url())->toEndWith('.jpg');

    $this->get(parse_url($jpeg->url(400), PHP_URL_PATH))->assertOk()->assertHeader('Content-Type', 'image/webp');
    expect(getimagesize(app(ImageCache::class)->path($jpeg->checksum, 400, 'webp')))->toMatchArray([0 => 400, 'mime' => 'image/webp'])
        // Đuôi định dạng gốc không còn là bản hợp lệ khi plugin đang bật.
        ->and($this->get(parse_url(str_replace('.webp', '.jpg', $jpeg->url(400)), PHP_URL_PATH))->status())->toBe(404);
});

it('tắt "Chuyển cả ảnh PNG" → PNG giữ định dạng gốc; tắt plugin → mọi ảnh quay về định dạng gốc', function () {
    $jpeg = $this->library->store(UploadedFile::fake()->image('dam.jpg', 1000, 1200));
    $png = $this->library->store(UploadedFile::fake()->image('logo.png', 1000, 500));

    ($this->system)(fn () => app(Settings::class)->set(MediaWebpServiceProvider::ID, 'convert_png', false));
    expect($png->url(400))->toEndWith('-w400.png')
        ->and($jpeg->url(400))->toEndWith('-w400.webp');

    ($this->system)(fn () => app(PluginManager::class)->disable(MediaWebpServiceProvider::ID));
    app(PluginActivation::class)->flush();
    expect($jpeg->url(400))->toEndWith('-w400.jpg');
});
