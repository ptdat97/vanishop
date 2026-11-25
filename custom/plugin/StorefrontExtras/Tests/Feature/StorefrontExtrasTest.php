<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginAssets;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Testing\StorefrontBlockContract;
use Modules\Tenancy\Contracts\Settings;
use Plugin\StorefrontExtras\Infrastructure\Blocks\BannerGridBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\BestsellersBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\CategoryGridBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\FeaturedCollectionBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\FeaturesBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\MarkdownBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\ProductTabsBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\RecentlyViewedBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\SlideshowBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\VideoHeroBlock;
use Plugin\StorefrontExtras\StorefrontExtrasServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

StorefrontBlockContract::define('vani.storefront-extras bestsellers', fn () => app(BestsellersBlock::class), config: ['title' => 'Bán chạy', 'limit' => 4, 'days' => '30']);
StorefrontBlockContract::define('vani.storefront-extras recently viewed', fn () => app(RecentlyViewedBlock::class), config: ['title' => 'Đã xem', 'layout' => 'scroll']);
StorefrontBlockContract::define('vani.storefront-extras video hero', fn () => app(VideoHeroBlock::class), config: ['video_url' => 'https://cdn.shop.test/hero.mp4', 'title' => 'Hè', 'autoplay' => true]);
StorefrontBlockContract::define('vani.storefront-extras slideshow', fn () => app(SlideshowBlock::class), config: ['slide_1_image' => 'https://cdn.shop.test/1.jpg', 'slide_1_title' => 'Mới']);
StorefrontBlockContract::define('vani.storefront-extras banner grid', fn () => app(BannerGridBlock::class), config: ['layout' => '3', 'cell_1_image' => '/img/a.jpg', 'cell_2_image' => '/img/b.jpg']);
StorefrontBlockContract::define('vani.storefront-extras product tabs', fn () => app(ProductTabsBlock::class), config: ['tab_1_label' => 'Mới', 'tab_1_source' => 'newest']);
StorefrontBlockContract::define('vani.storefront-extras features', fn () => app(FeaturesBlock::class), config: ['item_1_icon' => 'truck', 'item_1_title' => 'Giao nhanh']);
StorefrontBlockContract::define('vani.storefront-extras category grid', fn () => app(CategoryGridBlock::class), config: ['title' => 'Danh mục']);
StorefrontBlockContract::define('vani.storefront-extras featured collection', fn () => app(FeaturedCollectionBlock::class), config: ['collection' => '0']);
StorefrontBlockContract::define('vani.storefront-extras markdown', fn () => app(MarkdownBlock::class), config: ['body' => '**Chào**']);

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m, 'brand' => $this->brand] = C::store();
    $this->assets = storage_path('framework/testing/extras-assets-'.uniqid());
    app()->instance(PluginAssets::class, new PluginAssets($this->assets, 'https://shop.test/plugins'));
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(StorefrontExtrasServiceProvider::ID);
        app(PluginManager::class)->enable(StorefrontExtrasServiceProvider::ID);
    });
    app()->register(StorefrontExtrasServiceProvider::class);
    Cache::flush();
});

afterEach(fn () => File::deleteDirectory($this->assets));

it('trình chiếu chỉ bật điều hướng khi có nhiều ảnh và dùng thời gian chuyển đã cấu hình', function (int $count, int $interval) {
    $config = ['interval' => $interval, 'slide_1_image' => '/img/first.jpg'];
    if ($count > 1) {
        $config['slide_2_image'] = '/img/second.jpg';
    }

    $block = app(SlideshowBlock::class);
    $html = view($block->view(), $block->resolve($config, 'vi'))->render();

    if ($count === 1) {
        expect($html)->toContain('/img/first.jpg')->not->toContain('data-carousel-next', 'data-carousel=');

        return;
    }

    $options = ['rewind' => true, 'autoplay' => $interval ? ['delay' => $interval * 1000] : false];
    expect($html)->toContain('data-carousel-prev', 'data-carousel-next', 'data-carousel="'.e(json_encode($options)).'"');
})->with([[1, 6], [2, 0], [2, 5]]);

it('Bán chạy: theo số lượng đã bán, bỏ sản phẩm không còn bán, thiếu thì bù sản phẩm mới nhất', function () {
    $hidden = T::product($this->brand->id, ['status' => 'draft']);
    $other = T::product($this->brand->id, ['name' => 'Áo mới']);
    app()->instance(OrderStatistics::class, Mockery::mock(OrderStatistics::class, fn (MockInterface $mock) => $mock->shouldReceive('topStyles')->andReturn([
        ['style_id' => $hidden->id, 'quantity' => 9], ['style_id' => $this->s->style_id, 'quantity' => 5],
    ])));

    $products = app(BestsellersBlock::class)->resolve(['limit' => 2, 'days' => '30'], 'vi')['products'];
    expect(array_column($products, 'id'))->toBe([$this->s->style_id, $other->id]);
});

it('Video hero chỉ nhận https mp4/webm; Markdown bỏ HTML thô và link javascript:', function () {
    expect(fn () => app(VideoHeroBlock::class)->resolve(['video_url' => 'http://x.test/a.mp4'], 'vi'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(VideoHeroBlock::class)->resolve(['video_url' => 'https://x.test/a.mov'], 'vi'))->toThrow(InvalidArgumentException::class);

    $html = app(MarkdownBlock::class)->resolve(['body' => "<script>alert(1)</script>\n\n[bấm](javascript:alert(1)) **đậm**"], 'vi')['html'];
    expect($html)->not->toContain('<script')->not->toContain('javascript:')->toContain('<strong>đậm</strong>');
});

it('Video hero 1.2.0: video chọn từ Thư viện ảnh (id), di động riêng; ảnh đặt nhầm vào ô video không được dùng làm video', function () {
    $media = fn (string $name, string $mime): Media => Media::query()->create(['disk' => 'public', 'path' => "media/{$name}", 'folder' => '', 'original_name' => $name, 'mime_type' => $mime, 'size_bytes' => 1, 'width' => null, 'height' => null, 'checksum' => hash('sha256', $name)]);
    $desktop = $media('hero.mp4', 'video/mp4');
    $mobile = $media('doc.webm', 'video/webm');
    $photo = $media('anh.jpg', 'image/jpeg');

    $data = app(VideoHeroBlock::class)->resolve(['video' => $desktop->id, 'video_mobile' => $mobile->id, 'video_url' => 'https://cdn.shop.test/cu.mp4'], 'vi');
    expect($data['video'])->toBe(['src' => vani_media($desktop->id), 'type' => 'video/mp4'])
        ->and($data['videoMobile'])->toBe(['src' => vani_media($mobile->id), 'type' => 'video/webm'])
        ->and(app(VideoHeroBlock::class)->resolve(['video_url' => 'https://cdn.shop.test/cu.webm?v=2'], 'vi')['video'])->toBe(['src' => 'https://cdn.shop.test/cu.webm?v=2', 'type' => 'video/webm'])
        ->and(fn () => app(VideoHeroBlock::class)->resolve(['video' => $photo->id], 'vi'))->toThrow(InvalidArgumentException::class)
        ->and(collect(app(VideoHeroBlock::class)->fields())->whereIn('key', ['video', 'video_mobile'])->pluck('type')->all())->toBe(['video', 'video']);

    $html = view(app(VideoHeroBlock::class)->view(), $data)->render();
    expect($html)->toContain('src="'.vani_media($mobile->id).'" media="(max-width: 767px)" type="video/webm"')->toContain('type="video/mp4"');

    try {
        app(BuilderEngine::class)->save([['type' => 'extras_video_hero', 'config' => ['video' => $photo->id, 'poster' => $desktop->id, 'video_mobile' => 99999999]]]);
        $this->fail('Phải từ chối tệp sai loại hoặc không tồn tại.');
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toEqualCanonicalizing(['blocks.0.config.video', 'blocks.0.config.poster', 'blocks.0.config.video_mobile']);
    }

    // Video đang dùng ở khối trang chủ được khai báo như ảnh — Thư viện ảnh không cho xoá.
    app(BuilderEngine::class)->save([['type' => 'extras_video_hero', 'config' => ['video' => $desktop->id, 'video_mobile' => $mobile->id]]]);
    expect(DB::table('mediables')->where(['mediable_type' => 'storefront.builder', 'role' => 'home'])->pluck('media_id')->sort()->values()->all())->toBe([$desktop->id, $mobile->id]);
});

it('Sản phẩm theo tab: tab thiếu tên/nguồn hoặc không có sản phẩm bị bỏ; chọn tay giữ thứ tự id', function () {
    $tabs = app(ProductTabsBlock::class)->resolve([
        'tab_1_label' => 'Chọn', 'tab_1_source' => 'ids', 'tab_1_value' => "{$this->m->style_id}, {$this->s->style_id}",
        'tab_2_label' => 'Trống', 'tab_2_source' => 'category', 'tab_2_value' => 'khong-co',
        'tab_3_label' => '', 'tab_3_source' => 'newest',
    ], 'vi')['tabs'];

    expect(array_column($tabs, 'label'))->toBe(['Chọn']);
});

it('trang chủ render khối của plugin + script; giỏ có thanh "Đã xem" (khung rỗng, bỏ sản phẩm trong giỏ), tắt được', function () {
    T::seed(fn () => app(BuilderEngine::class)->save([
        ['type' => 'extras_features', 'config' => ['item_1_icon' => 'truck', 'item_1_title' => 'Giao nhanh 2h']],
        ['type' => 'extras_recently_viewed', 'config' => ['title' => 'Bạn đã xem gần đây']],
    ]));
    $this->get('/')->assertOk()->assertSee('Giao nhanh 2h')->assertSee('data-extras-recent', false)
        ->assertSee('<path d="M3 7h11v8H3z', false) // đường SVG biểu tượng do khối dựng sẵn
        ->assertSee('https://shop.test/plugins/vani.storefront-extras/extras.', false);

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/gio-hang')->assertOk()->assertSee('data-exclude-slugs="'.$this->s->style->slug.'"', false);

    T::seed(fn () => app(Settings::class)->set(StorefrontExtrasServiceProvider::ID, 'recent_on_cart', false));
    $this->get('/gio-hang')->assertOk()->assertDontSee('data-extras-recent', false);
});

it('lưới banner: ô lớn (bố cục 1 ô, ô đầu của bố cục 3 ô) rộng cả khung, ô khác nửa khung — partial ảnh của theme tính sizes', function () {
    $cells = app(BannerGridBlock::class)->resolve(['layout' => '3', 'cell_1_image' => '/img/a.jpg', 'cell_2_image' => '/img/b.jpg', 'cell_2_link' => 'javascript:alert(1)'], 'vi')['cells'];

    expect(array_map(fn (array $cell) => [$cell['wide'], $cell['image_layout'], $cell['href']], $cells))->toBe([
        [true, [1152, 1, 1], '#'],
        [false, [1152, 2, 1], '#'],
    ])->and(app(BannerGridBlock::class)->resolve(['layout' => '1', 'cell_1_image' => '/img/a.jpg'], 'vi')['cells'][0]['wide'])->toBeTrue();
});

it('lặp slide kiểm tra ảnh từng mục, bỏ khoá lạ và theo dõi ảnh đã lưu', function () {
    Storage::fake('public');
    $image = T::seed(fn () => app(MediaLibrary::class)->store(UploadedFile::fake()->image('slide.jpg', 800, 600)));
    $this->actingAs(T::staff(['admin.access', 'storefront.manage']), 'staff');
    $url = route('admin.storefront.builder.edit');
    $block = fn (array $slides): array => ['blocks' => [['type' => 'extras_slideshow', 'config' => ['slides' => $slides]]]];

    $this->put($url, $block([['media' => $image->id], ['media' => 999999]]))->assertSessionHasErrors('blocks.0.config.slides.1.media');
    $this->put($url, $block(array_fill(0, 6, [])))->assertSessionHasErrors('blocks.0.config.slides');

    $this->put($url, $block([['media' => $image->id, 'title' => 'Mùa hè', 'unknown' => 'bỏ']]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('mediables', ['media_id' => $image->id, 'mediable_type' => 'storefront.builder', 'role' => 'home']);
    $this->get($url)->assertInertia(fn ($page) => $page->where('blocks.0.config.slides.0', ['media' => $image->id, 'title' => 'Mùa hè']));
    $this->get('/')->assertSee('Mùa hè');
});

it('lưới danh mục trang chủ giữ thứ tự đã chọn trên Blade và API, bỏ danh mục ẩn hoặc bị xoá', function () {
    Storage::fake('public');
    [$coats, $dresses, $hidden, $deleted, $imageUrl] = T::seed(function (): array {
        $coats = Category::factory()->create(['slug' => 'ao-khoac', 'position' => 1]);
        $coats->syncTranslations(['vi' => ['name' => 'Áo khoác'], 'en' => ['name' => 'Coats', 'slug' => 'coats']]);
        $library = app(MediaLibrary::class);
        $image = $library->store(UploadedFile::fake()->image('coats.jpg', 800, 600));
        $library->attachSingle($coats, 'image', $image);
        $dresses = Category::factory()->create(['slug' => 'dam', 'position' => 2]);
        $dresses->syncTranslations(['vi' => ['name' => 'Đầm'], 'en' => ['name' => 'Dresses', 'slug' => 'dresses']]);
        $hidden = Category::factory()->create(['slug' => 'mua-cu']);
        $deleted = Category::factory()->create(['slug' => 'da-xoa']);

        app(BuilderEngine::class)->save([['type' => 'extras_category_grid', 'config' => [
            'category_1' => (string) $dresses->id,
            'category_2' => (string) $hidden->id,
            'category_3' => (string) $coats->id,
            'category_4' => (string) $deleted->id,
        ]]]);

        return [$coats, $dresses, $hidden, $deleted, $image->url()];
    });
    T::seed(function () use ($hidden, $deleted): void {
        $hidden->update(['status' => 'hidden']);
        $deleted->delete();
    });

    $this->getJson('/api/storefront/v1/pages/home', ['X-Vani-Locale' => 'en'])->assertOk()
        ->assertJsonPath('data.0.type', 'extras_category_grid')
        ->assertJsonCount(2, 'data.0.data.categories')
        ->assertJsonPath('data.0.data.categories.0', ['id' => $dresses->id, 'slug' => 'dresses', 'name' => 'Dresses', 'image_url' => null])
        ->assertJsonPath('data.0.data.categories.1', ['id' => $coats->id, 'slug' => 'coats', 'name' => 'Coats', 'image_url' => $imageUrl]);
    $html = $this->get('/en')->assertOk()->assertSeeInOrder(['Dresses', 'Coats'])->getContent();
    $grid = str($html)->after('data-block="extras_category_grid"')->before('</section>')->toString();
    expect($grid)->toContain(vani_route('storefront.category', 'dresses', 'en'), vani_route('storefront.category', 'coats', 'en'), 'src="'.e($imageUrl).'"', 'viewBox="0 0 48 48"')
        ->and(substr_count($grid, 'data-category-card'))->toBe(2);
});

it('bộ sưu tập nổi bật dùng đúng locale, ảnh và thẻ sản phẩm; ẩn khối khi bộ sưu tập bị ẩn', function () {
    Storage::fake('public');
    [$collection, $cover] = T::seed(function (): array {
        $collection = ProductCollection::query()->create(['slug' => 'mua-he', 'status' => 'active']);
        $collection->syncTranslations(['vi' => ['name' => 'Mùa hè'], 'en' => ['name' => 'Summer edit', 'slug' => 'summer-edit']]);
        $collection->styles()->attach($this->s->style_id);
        $cover = app(MediaLibrary::class)->store(UploadedFile::fake()->image('cover.jpg', 1200, 1500));
        app(BuilderEngine::class)->save([['type' => 'extras_featured_collection', 'config' => [
            'collection' => (string) $collection->id, 'image' => $cover->id, 'image_alt' => 'Ảnh bìa mùa hè',
            'description' => '<strong>Phối đồ mùa hè</strong>',
        ]]]);

        return [$collection, $cover];
    });

    $this->get('/')->assertOk()->assertSee('data-block="extras_featured_collection"', false)
        ->assertSee('Mùa hè')->assertSee('alt="Ảnh bìa mùa hè"', false)->assertSee('width="1200"', false)->assertSee('height="1500"', false)
        ->assertSee('srcset="'.$cover->srcset().'"', false)
        ->assertSee('<strong>Phối đồ mùa hè</strong>')->assertDontSee('<strong>Phối đồ mùa hè</strong>', false)
        ->assertSee('data-vani-product="'.$this->s->style->slug.'"', false);
    $this->get('/en')->assertOk()->assertSee('Summer edit')->assertSee('/en/collections/summer-edit');
    $this->getJson('/api/storefront/v1/pages/home', ['X-Vani-Locale' => 'en'])->assertOk()
        ->assertJsonPath('data.0.data.title', 'Summer edit')
        ->assertJsonCount(1, 'data.0.data.products')
        ->assertJsonPath('data.0.data.products.0.id', $this->s->style_id);

    T::seed(fn () => $collection->update(['status' => 'hidden']));
    $this->get('/')->assertOk()->assertDontSee('data-block="extras_featured_collection"', false);
});
