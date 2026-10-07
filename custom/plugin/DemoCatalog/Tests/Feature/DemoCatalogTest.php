<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\DemoCatalog\DemoCatalogServiceProvider;
use Plugin\DemoCatalog\Infrastructure\PhotoLibrary;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/** Ảnh JPEG nhỏ, màu khác nhau để checksum khác nhau. */
function demoPhoto(string $directory, string $name, int $seed, int $width = 60): void
{
    $image = imagecreatetruecolor($width, (int) ($width * 1.5));
    imagefill($image, 0, 0, imagecolorallocate($image, $seed % 255, ($seed * 7) % 255, ($seed * 13) % 255));
    imagejpeg($image, $directory.'/'.$name, 90);
    imagedestroy($image);
}

beforeEach(function () {
    Storage::fake('public');
    C::store(); // có kho giao online do VaniShop quản lý
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(DemoCatalogServiceProvider::ID);
        app(PluginManager::class)->enable(DemoCatalogServiceProvider::ID);
    });
    app()->register(DemoCatalogServiceProvider::class);

    $this->source = storage_path('framework/testing/demo-photos-'.uniqid());
    File::ensureDirectoryExists($this->source);
    // Bộ 1: VNQ06582/06585/06590 (cùng bộ đồ) + bản tải lại của 06582 (lớn hơn → được giữ).
    demoPhoto($this->source, '_1773800497VNQ06582 copy_1.jpg', 1);
    demoPhoto($this->source, 'vnq06582-copy-1773800503.jpg', 2, 90);
    demoPhoto($this->source, '_1773800496VNQ06585 copy_1.jpg', 3);
    demoPhoto($this->source, '_1773800497VNQ06590 copy_1.jpg', 4);
    // Bộ 2: _DSC6767 (số khung cách xa bộ 1, khác tiền tố) + file không phải ảnh bị bỏ qua.
    demoPhoto($this->source, '_DSC6767.jpg', 5);
    File::put($this->source.'/ghi-chu.txt', 'không phải ảnh');
});

afterEach(fn () => File::deleteDirectory($this->source));

it('gom ảnh: chuẩn hoá tên, bỏ ảnh tải lại (giữ bản lớn), nhóm theo số khung liền nhau', function () {
    expect(PhotoLibrary::normalize('_1773799834VNQ07102 copy_1.jpg'))->toBe('vnq07102')
        ->and(PhotoLibrary::normalize('dtt_9853-1776220866.jpg'))->toBe('dtt9853')
        ->and(PhotoLibrary::normalize('_1772166247_DSC7352_1.jpg'))->toBe('dsc7352');

    $groups = app(PhotoLibrary::class)->groups($this->source);
    expect(array_column($groups, 'key'))->toBe(['dsc6767', 'vnq6582'])
        ->and(array_map('basename', $groups[1]['files']))->toBe(['vnq06582-copy-1773800503.jpg', '_1773800496VNQ06585 copy_1.jpg', '_1773800497VNQ06590 copy_1.jpg']);
});

it('nhập sản phẩm có ảnh, giá, tồn qua contract Core; chạy lại không trùng', function () {
    $this->artisan('vani:demo:catalog', ['--source' => $this->source])->expectsOutputToContain('Xong: 2 sản phẩm (2 mới), 4 ảnh mới, 6 SKU.')->assertSuccessful();

    $style = DB::table('styles')->where('style_code', 'VS-VNQ6582')->first();
    expect($style)->not->toBeNull()
        ->and(DB::table('brands')->where('id', $style->brand_id)->value('slug'))->toBe('vani-studio')
        ->and(DB::table('variants')->where('style_id', $style->id)->pluck('sku')->all())->toHaveCount(3)
        ->and(DB::table('mediables')->where('role', 'gallery')->count())->toBe(4)
        ->and(DB::table('media')->max('width'))->toBeLessThanOrEqual(1600);

    $skus = DB::table('variants')->where('sku', 'like', 'VS-%')->pluck('id');
    expect(DB::table('prices')->whereIn('variant_id', $skus)->count())->toBe(6)
        ->and(DB::table('stock_movements')->where('reason', 'Tồn đầu kỳ (demo)')->exists())->toBeTrue();

    // Storefront: sản phẩm hiện với ảnh.
    $this->getJson('/api/storefront/v1/products/'.$style->slug)->assertOk()->assertJsonPath('data.style_code', 'VS-VNQ6582');

    $this->artisan('vani:demo:catalog', ['--source' => $this->source])->expectsOutputToContain('Xong: 2 sản phẩm (0 mới), 0 ảnh mới, 6 SKU.')->assertSuccessful();
    expect(DB::table('styles')->where('style_code', 'like', 'VS-%')->count())->toBe(2)
        ->and(DB::table('mediables')->where('role', 'gallery')->count())->toBe(4);
});

it('--dry-run chỉ in kế hoạch; thư mục nguồn không có → báo lỗi', function () {
    $this->artisan('vani:demo:catalog', ['--source' => $this->source, '--dry-run' => true])->expectsOutputToContain('2 sản phẩm sẽ được nhập.')->assertSuccessful();
    expect(DB::table('styles')->where('style_code', 'like', 'VS-%')->exists())->toBeFalse();

    $this->artisan('vani:demo:catalog', ['--source' => '/khong/co/thu-muc'])->expectsOutputToContain('Không thấy thư mục ảnh demo')->assertFailed();
});
