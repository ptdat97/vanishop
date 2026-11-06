<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Storefront\Application\Blocks\BrandGridBlock;
use Modules\Storefront\Application\Blocks\HeroBlock;
use Modules\Storefront\Application\Blocks\ProductGridBlock;
use Modules\Storefront\Application\Blocks\RichTextBlock;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Storefront\Testing\StorefrontBlockContract;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

StorefrontBlockContract::define('core hero', fn () => app(HeroBlock::class), ['title' => 'Hè 2026', 'link_url' => '/thuong-hieu', 'link_label' => 'Xem']);
StorefrontBlockContract::define('core product_grid', fn () => app(ProductGridBlock::class), ['source' => 'newest', 'limit' => 4]);
StorefrontBlockContract::define('core brand_grid', fn () => app(BrandGridBlock::class), ['title' => 'Thương hiệu']);
StorefrontBlockContract::define('core rich_text', fn () => app(RichTextBlock::class), ['body' => "Dòng 1\nDòng 2"]);

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');
    $this->url = '/admin/storefront/home-blocks';
});

it('chưa cấu hình → trang chủ mặc định; cấu hình khối → trang chủ render đúng thứ tự, escape nội dung, chặn link javascript:', function () {
    $this->get('/')->assertOk()->assertSee('Sản phẩm mới');

    $this->put($this->url, ['blocks' => [
        ['type' => 'hero', 'config' => ['title' => 'Bộ sưu tập Hè', 'link_label' => 'Mua ngay', 'link_url' => 'javascript:alert(1)']],
        ['type' => 'rich_text', 'config' => ['body' => '<script>alert(1)</script>Miễn phí giao']],
        ['type' => 'product_grid', 'config' => ['title' => 'Bán chạy', 'source' => 'brand', 'slug' => $this->brand->slug, 'limit' => 4]],
    ]])->assertSessionHasNoErrors();

    $html = $this->get('/')->assertOk()->assertDontSee('Sản phẩm mới')->getContent();
    expect($html)->toContain('Bộ sưu tập Hè')->toContain('Đầm lụa')->toContain('&lt;script&gt;')
        ->not->toContain('javascript:alert')
        ->and(strpos($html, 'data-block="hero"'))->toBeLessThan(strpos($html, 'data-block="product_grid"'))
        ->and(AuditLog::query()->where('action', 'storefront.home_blocks_updated')->exists())->toBeTrue();
});

it('validate theo fields của từng loại; loại không có bị từ chối; dùng lại mặc định', function () {
    $this->put($this->url, ['blocks' => [['type' => 'hero', 'config' => []]]])->assertSessionHasErrors('blocks.0.config.title');
    $this->put($this->url, ['blocks' => [['type' => 'khong_co', 'config' => []]]])->assertSessionHasErrors('blocks.0.type');

    $this->put($this->url, ['blocks' => [['type' => 'brand_grid', 'config' => ['title' => 'TH', 'la' => 'x']]]])->assertSessionHasNoErrors();
    $this->get($this->url)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/HomeBlocks')
        ->where('blocks', [['type' => 'brand_grid', 'config' => ['title' => 'TH']]])->where('configured', true));

    $this->delete($this->url)->assertSessionHasNoErrors();
    $this->get('/')->assertSee('Sản phẩm mới');
});

it('khối lỗi khi render bị bỏ, khối khác vẫn hiện; không có quyền → 403', function () {
    $broken = new class implements StorefrontBlock
    {
        public function type(): string
        {
            return 'broken';
        }

        public function label(): string
        {
            return 'Hỏng';
        }

        public function fields(): array
        {
            return [];
        }

        public function resolve(array $config, string $locale): array
        {
            throw new RuntimeException('plugin hỏng');
        }

        public function view(): string
        {
            return 'theme::blocks.rich_text';
        }
    };
    app()->instance($broken::class, $broken);
    app(Extensions::class)->contribute(StorefrontBlock::TAG, $broken::class, 'vani.cod');

    $this->put($this->url, ['blocks' => [['type' => 'broken', 'config' => []], ['type' => 'rich_text', 'config' => ['body' => 'Còn hiện']]]])->assertSessionHasNoErrors();
    $this->get('/')->assertOk()->assertSee('Còn hiện');

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    $this->get($this->url)->assertForbidden();
});

it('khối có trường ảnh (media): ảnh Thư viện ảnh có srcset, được khai báo đang dùng; xoá cấu hình thì bỏ khai báo', function () {
    Storage::fake('public');
    $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('hero.jpg', 2000, 1200));

    $this->put($this->url, ['blocks' => [['type' => 'hero', 'config' => ['title' => 'Hè', 'image' => 'abc']]]])->assertSessionHasErrors('blocks.0.config.image');
    $this->put($this->url, ['blocks' => [['type' => 'hero', 'config' => ['title' => 'Hè', 'image' => $media->id]]]])->assertSessionHasNoErrors();

    expect(DB::table('mediables')->where(['media_id' => $media->id, 'mediable_type' => 'storefront.home'])->exists())->toBeTrue();
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('srcset="'.e($media->srcset()).'"')->toContain('fetchpriority="high"');

    app(PageBlocks::class)->resetHome();
    expect(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeFalse();
});

it('khối trang chủ không còn ô URL ảnh: mọi trường ảnh là ảnh Thư viện ảnh (media)', function () {
    $urlImageFields = [];
    foreach (app(PageBlocks::class)->types() as $type => $block) {
        foreach ($block->fields() as $field) {
            if ($field->type !== 'media' && preg_match('/(^|_)(image|poster)(_url)?$|image_url|logo/', $field->key) === 1) {
                $urlImageFields[] = "{$type}.{$field->key}";
            }
        }
    }

    expect($urlImageFields)->toBe([]);
});

it('migration chuyển URL ảnh cũ của khối sang id Thư viện ảnh (bản thu nhỏ hoặc file gốc); URL ngoài giữ nguyên', function () {
    Storage::fake('public');
    $library = app(MediaLibrary::class);
    $hero = $library->store(UploadedFile::fake()->image('hero.jpg', 2000, 1200));
    $slide = $library->store(UploadedFile::fake()->image('slide.jpg', 1800, 900));
    app(Settings::class)->set('core', 'storefront.home_blocks', [
        ['type' => 'hero', 'config' => ['title' => 'Hè', 'image_url' => $hero->url(800)]],
        ['type' => 'rich_text', 'config' => ['body' => 'x']],
        ['type' => 'hero', 'config' => ['title' => 'Ngoài', 'image_url' => 'https://cdn.khac.test/a.jpg']],
        ['type' => 'hero', 'config' => ['title' => 'Gốc', 'image_url' => $slide->url()]],
        // Khối nhiều ảnh: mọi ô đều được chuyển (không chỉ ô cuối).
        ['type' => 'extras_banner_grid', 'config' => ['layout' => '2', 'cell_1_image' => $hero->url(), 'cell_1_title' => 'A', 'cell_2_image' => $slide->url(400)]],
    ]);

    (require base_path('modules/Storefront/Persistence/Database/migrations/2026_11_06_000300_home_block_images_to_media.php'))->up();
    app()->forgetInstance(Settings::class);
    $blocks = app(PageBlocks::class)->home();

    expect($blocks[0]['config'])->toBe(['title' => 'Hè', 'image' => $hero->id])
        ->and($blocks[2]['config'])->toBe(['title' => 'Ngoài', 'image_url' => 'https://cdn.khac.test/a.jpg'])
        ->and($blocks[3]['config'])->toBe(['title' => 'Gốc', 'image' => $slide->id])
        ->and($blocks[4]['config'])->toBe(['layout' => '2', 'cell_1_title' => 'A', 'cell_1_media' => $hero->id, 'cell_2_media' => $slide->id])
        ->and(DB::table('mediables')->where('mediable_type', 'storefront.home')->pluck('media_id')->sort()->values()->all())->toBe(collect([$hero->id, $slide->id])->sort()->values()->all());
    // Cấu hình cũ còn URL ngoài vẫn hiện.
    $this->get('/')->assertOk()->assertSee('https://cdn.khac.test/a.jpg', false);
});
