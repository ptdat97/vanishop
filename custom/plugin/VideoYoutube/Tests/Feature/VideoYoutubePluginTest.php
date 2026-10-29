<?php

use Modules\Catalog\Persistence\Models\Video;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\VideoYoutube\Infrastructure\YoutubeDriver;
use Plugin\VideoYoutube\VideoYoutubeServiceProvider;

require_once __DIR__.'/../../../../../modules/Catalog/Tests/Feature/CatalogTestHelpers.php';

beforeEach(function () {
    $this->system = fn (Closure $action) => app(CurrentContext::class)->runAs(ContextScope::system('test'), $action);
    ($this->system)(function () {
        app(PluginManager::class)->install(VideoYoutubeServiceProvider::ID);
        app(PluginManager::class)->enable(VideoYoutubeServiceProvider::ID);
    });
    app()->register(VideoYoutubeServiceProvider::class);
    app(PluginActivation::class)->flush();

    $this->actingAs(T::staff(), 'staff');
    $this->dress = T::product(null, ['slug' => 'dam']);
    $this->url = "/admin/catalog/products/{$this->dress->id}/videos";
});

it('nhận ID từ các dạng link YouTube', function (string $input, ?string $expected) {
    expect((new YoutubeDriver)->reference($input))->toBe($expected);
})->with([
    'ID trần' => ['dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42', 'dQw4w9WgXcQ'],
    'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'dQw4w9WgXcQ'],
    'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
    'nền tảng khác' => ['https://vimeo.com/dQw4w9WgXcQ', null],
    'ID sai độ dài' => ['https://youtu.be/abc', null],
    'kênh' => ['https://www.youtube.com/@vanishop', null],
]);

it('thêm video từ link; storefront trả URL nhúng nocookie theo thứ tự', function () {
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'title' => 'Lookbook'])->assertSessionHasNoErrors();
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'aaaaaaaaaaa'])->assertSessionHasNoErrors();

    $this->getJson('/api/storefront/v1/products/dam')
        ->assertJsonCount(2, 'data.videos')
        ->assertJsonPath('data.videos.0.title', 'Lookbook')
        ->assertJsonPath('data.videos.0.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->assertJsonPath('data.videos.0.thumbnail_url', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg');

    $ids = T::seed(fn () => $this->dress->videos()->pluck('videos.id')->all());
    $this->put("{$this->url}/order", ['order' => array_reverse($ids)])->assertSessionHasNoErrors();
    $this->getJson('/api/storefront/v1/products/dam')->assertJsonPath('data.videos.0.reference', 'aaaaaaaaaaa');
});

it('báo link không hợp lệ và video đã thêm', function () {
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'https://vimeo.com/1'])->assertSessionHasErrors('url');

    $this->post($this->url, ['driver' => 'youtube', 'url' => 'dQw4w9WgXcQ']);
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])
        ->assertSessionHasErrors(['url' => 'Sản phẩm đã có video này.']);
});

it('một video dùng chung nhiều sản phẩm; gỡ khỏi sản phẩm cuối cùng thì xoá video', function () {
    $shirt = T::product(null, ['slug' => 'ao']);
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'dQw4w9WgXcQ']);
    $this->post("/admin/catalog/products/{$shirt->id}/videos", ['driver' => 'youtube', 'url' => 'dQw4w9WgXcQ']);
    $video = T::seed(fn () => Video::query()->sole());

    $this->delete("{$this->url}/{$video->id}")->assertSessionHasNoErrors();
    expect(T::seed(fn () => Video::query()->count()))->toBe(1);
    $this->delete("{$this->url}/{$video->id}")->assertNotFound();

    $this->delete("/admin/catalog/products/{$shirt->id}/videos/{$video->id}");
    expect(T::seed(fn () => Video::query()->count()))->toBe(0);
});

it('tắt plugin: video không hiện trên storefront và không thêm mới được', function () {
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'dQw4w9WgXcQ']);

    ($this->system)(fn () => app(PluginManager::class)->disable(VideoYoutubeServiceProvider::ID));
    app(PluginActivation::class)->flush();

    $this->getJson('/api/storefront/v1/products/dam')->assertJsonPath('data.videos', []);
    $this->post($this->url, ['driver' => 'youtube', 'url' => 'aaaaaaaaaaa'])
        ->assertSessionHasErrors(['driver' => 'Nền tảng video chưa được bật (cần plugin, vd. vani.video-youtube).']);
});
