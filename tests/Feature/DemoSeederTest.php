<?php

use Database\Seeders\Demo\PhotoLibrary;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Application\Menus\MenuRepository;

/*
| Dữ liệu demo toàn dự án (database/seeders/DemoSeeder — gộp từ plugin vani.demo-catalog cũ, 2026-11-05).
*/

/** Ảnh JPEG nhỏ, màu khác nhau để checksum khác nhau. */
function demoSeedPhoto(string $directory, string $name, int $seed, int $width = 60): void
{
    $image = imagecreatetruecolor($width, (int) ($width * 1.5));
    imagefill($image, 0, 0, imagecolorallocate($image, $seed % 255, ($seed * 7) % 255, ($seed * 13) % 255));
    imagejpeg($image, $directory.'/'.$name, 90);
    imagedestroy($image);
}

beforeEach(function () {
    Storage::fake('public');
    Cache::flush();
    $this->source = storage_path('framework/testing/demo-photos-'.uniqid());
    File::ensureDirectoryExists($this->source);
    // Bộ 1: VNQ06582/06585/06590 (cùng bộ đồ) + bản tải lại của 06582 (lớn hơn → được giữ). Bộ 2: _DSC6767.
    demoSeedPhoto($this->source, '_1773800497VNQ06582 copy_1.jpg', 1);
    demoSeedPhoto($this->source, 'vnq06582-copy-1773800503.jpg', 2, 90);
    demoSeedPhoto($this->source, '_1773800496VNQ06585 copy_1.jpg', 3);
    demoSeedPhoto($this->source, '_1773800497VNQ06590 copy_1.jpg', 4);
    demoSeedPhoto($this->source, '_DSC6767.jpg', 5);
    File::put($this->source.'/ghi-chu.txt', 'không phải ảnh');
    config(['vanishop.demo.images' => $this->source]);
});

afterEach(fn () => File::deleteDirectory($this->source));

it('gom ảnh: chuẩn hoá tên, bỏ ảnh tải lại (giữ bản lớn), nhóm theo số khung liền nhau; thư mục không có → báo lỗi', function () {
    expect(PhotoLibrary::normalize('_1773799834VNQ07102 copy_1.jpg'))->toBe('vnq07102')
        ->and(PhotoLibrary::normalize('dtt_9853-1776220866.jpg'))->toBe('dtt9853')
        ->and(PhotoLibrary::normalize('_1772166247_DSC7352_1.jpg'))->toBe('dsc7352');

    $groups = app(PhotoLibrary::class)->groups($this->source);
    expect(array_column($groups, 'key'))->toBe(['dsc6767', 'vnq6582'])
        ->and(array_map('basename', $groups[1]['files']))->toBe(['vnq06582-copy-1773800503.jpg', '_1773800496VNQ06585 copy_1.jpg', '_1773800497VNQ06590 copy_1.jpg'])
        ->and(fn () => app(PhotoLibrary::class)->groups('/khong/co/thu-muc'))->toThrow(InvalidArgumentException::class);
});

it('bộ ảnh đi kèm: 56 ảnh đã thu về ≤ 1600px → 25 sản phẩm', function () {
    $source = base_path('database/seeders/Demo/photos');
    $groups = app(PhotoLibrary::class)->groups($source);

    expect($groups)->toHaveCount(25)
        ->and(array_sum(array_map(fn (array $group): int => count($group['files']), $groups)))->toBe(56)
        ->and(collect($groups)->firstWhere('key', 'vnq5143')['files'])->toHaveCount(4)
        ->and(max(array_map(fn (string $file): int => max(array_slice(getimagesize($file) ?: [0, 0], 0, 2)), glob($source.'/*.jpg') ?: [])))->toBeLessThanOrEqual(1600);
});

it('DemoSeeder dựng cả cửa hàng (catalog có ảnh, kho, nội dung, trang chủ, menu, đơn nhiều trạng thái); chạy lại không trùng', function () {
    $this->seed(DemoSeeder::class);

    $product = DB::table('products')->where('product_code', 'VS-VNQ6582')->first();
    $counts = fn (): array => [
        'products' => DB::table('products')->count(), 'media' => DB::table('mediables')->where('role', 'gallery')->count(),
        'locations' => DB::table('locations')->count(), 'orders' => DB::table('orders')->count(), 'posts' => DB::table('plg_cms_posts')->count(),
        'lookbooks' => DB::table('plg_lookbooks')->count(), 'collections' => DB::table('collections')->count(), 'promotions' => DB::table('promotions')->count(),
        'pricing_campaigns' => DB::table('plg_pricing_campaigns')->count(), 'coupons' => DB::table('plg_coupons')->count(), 'vouchers' => DB::table('vouchers')->count(),
    ];

    // Catalog: danh mục con của "Nữ" theo tên + "Hàng mới về", ảnh, giá, sale, bộ sưu tập, tồn ở kho online + cửa hàng.
    expect($product)->not->toBeNull()
        ->and(DB::table('categories')->where('id', $product->primary_category_id)->value('slug'))->toBe('ao') // nhóm ảnh thứ hai → "Áo sơ mi lụa cổ đức"
        ->and(DB::table('category_product')->where('product_id', $product->id)->count())->toBe(2)
        ->and(DB::table('variants')->where('product_id', $product->id)->count())->toBe(3)
        ->and(DB::table('media')->max('width'))->toBeLessThanOrEqual(1600)
        ->and(DB::table('prices')->join('price_lists', 'price_lists.id', '=', 'prices.price_list_id')->where('price_lists.type', 'base')->count())->toBe(6)
        ->and(DB::table('locations')->pluck('code')->sort()->values()->all())->toBe(['CH-Q1', 'KHO-CHINH'])
        ->and(DB::table('stock_levels')->count())->toBe(12)
        ->and(DB::table('collections')->count())->toBeGreaterThan(0)
        ->and(DB::table('promotions')->whereNull('managed_by')->count())->toBe(2);

    $flashSale = DB::table('plg_pricing_campaigns')->where('template', 'flash_sale')->first();
    $sharedCoupon = DB::table('plg_coupons')->where('code', 'VANIDEMO10')->first();
    $privateCoupon = DB::table('plg_coupons')->where('kind', 'template')->first();
    $customerId = DB::table('customers')->where('email', 'lan.nguyen@example.com')->value('id');
    expect($flashSale)->not->toBeNull()
        ->and($sharedCoupon)->not->toBeNull()
        ->and($privateCoupon)->not->toBeNull()
        ->and($customerId)->not->toBeNull();

    $this->assertDatabaseHas('price_lists', ['id' => $flashSale->price_list_id, 'managed_by' => 'vani.pricing-campaign']);
    $this->assertDatabaseHas('campaigns', ['id' => $flashSale->campaign_id, 'managed_by' => 'vani.pricing-campaign']);
    $this->assertDatabaseHas('price_quotas', ['price_list_id' => $flashSale->price_list_id, 'scope' => 'list', 'quota_limit' => 10]);
    $this->assertDatabaseHas('promotions', ['id' => $sharedCoupon->promotion_id, 'managed_by' => 'vani.coupon-code']);
    $this->assertDatabaseHas('vouchers', ['promotion_id' => $sharedCoupon->promotion_id, 'code' => 'VANIDEMO10']);
    $this->assertDatabaseHas('vouchers', ['promotion_id' => $privateCoupon->promotion_id, 'customer_id' => $customerId, 'usage_limit' => 1]);

    // Nội dung (plugin bật sẵn), giao diện.
    expect(DB::table('plg_cms_pages')->where('status', 'published')->count())->toBe(4)
        ->and(DB::table('plg_cms_posts')->count())->toBe(3)
        ->and(DB::table('plg_lookbooks')->count())->toBeGreaterThan(0)
        ->and(DB::table('plg_size_charts')->where('is_default', true)->count())->toBe(1)
        ->and(DB::table('plg_cms_category_banners')->count())->toBeGreaterThan(0)
        ->and(app(BuilderEngine::class)->blocks())->not->toBeEmpty()
        ->and(app(MenuRepository::class)->published('header'))->not->toBeEmpty()
        ->and(app(MenuRepository::class)->published('footer_1'))->not->toBeEmpty();

    // Đơn đi đúng luồng: cần xác nhận (COD mặc định chờ nhân viên — ADR-046), đang xử lý, đang giao, đã giao (COD đã thu),
    // hoàn tất, đã huỷ; tồn khớp sổ biến động.
    $orders = DB::table('orders')->where('note', '[demo] Đơn mẫu')->get();
    expect($orders)->toHaveCount(12)
        ->and($orders->pluck('order_status')->unique()->sort()->values()->all())->toBe(['cancelled', 'completed', 'pending', 'processing'])
        ->and($orders->where('payment_status', 'cod_collected')->count())->toBeGreaterThan(0)
        ->and($orders->whereIn('fulfillment_status', ['shipped'])->count())->toBeGreaterThan(0);
    $this->artisan('vani:inventory:verify')->assertSuccessful();

    // Storefront hiển thị được.
    $this->get('/')->assertOk()->assertSee('Hàng mới về');
    $this->get('/danh-muc/dam')->assertOk()->assertSee('data-vani-cms-category-banner', false);
    $this->get('/san-pham/'.$product->slug)->assertOk()->assertSee('VANIDEMO30K');
    $this->get('/uu-dai')->assertOk()->assertSee('Flash sale Vani Studio')->assertSee('Hot deal sắp mở');
    $this->get('/bo-suu-tap/thu-dong-2026')->assertOk()
        ->assertSeeInOrder(['data-collection-banner', 'Thu Đông 2026', 'Len, dạ và lụa cho mùa se lạnh.', 'data-vani-listing'], false);
    $this->get(route('storefront.brands'))->assertOk();
    $this->get(route('storefront.cart'))->assertOk();

    $before = $counts();
    $this->seed(DemoSeeder::class);
    expect($counts())->toBe($before);
});
