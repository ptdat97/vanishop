<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s, 'm' => $this->m] = C::store();
    $this->product = $this->s->style;
    $this->urbanx = Brand::factory()->create(['slug' => 'urbanx', 'name' => 'Urbanx']);
    T::product($this->urbanx->id, ['name' => 'Áo thun Urbanx', 'slug' => 'ao-thun-urbanx']);
    $this->checkout = fn (array $overrides = []) => array_replace_recursive([
        'contact' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'email' => 'lan@example.com'],
        'shipping_address' => ['province_name' => 'TP. Hồ Chí Minh', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi'],
        'shipping_method' => 'standard',
        'payment_method' => 'cod',
        'expected_total' => 330_000,
        'idempotency_key' => 'native-order-0001',
    ], $overrides);
});

it('render SSR: trang chủ, thương hiệu, tìm kiếm, PDP có giá và form thêm giỏ dùng được khi tắt JS', function () {
    $this->get('/')->assertOk()->assertSee('Đầm lụa')->assertSee('Urbanx');
    $this->get('/thuong-hieu')->assertOk()->assertSee('Urbanx');
    $this->get('/tim-kiem?q=dam')->assertOk()->assertSee('Đầm lụa')->assertDontSee('Áo thun Urbanx');

    $this->get("/san-pham/{$this->product->slug}")->assertOk()
        ->assertSee('Đầm lụa')
        ->assertSee('200.000 ₫ – 300.000 ₫')
        ->assertSee('action="'.route('storefront.cart.add').'"', false)
        ->assertSee('name="variant_id" value="'.$this->s->id.'"', false)
        ->assertSee('"@type":"Product"', false)
        ->assertDontSee('display:none', false);
});

it('trang thương hiệu chỉ có sản phẩm của brand; brand ẩn, danh mục/sản phẩm không có → 404', function () {
    $this->get('/thuong-hieu/urbanx')->assertOk()->assertSee('Áo thun Urbanx')->assertDontSee('Đầm lụa');

    $this->urbanx->update(['status' => 'hidden']);
    $this->get('/thuong-hieu/urbanx')->assertNotFound();
    $this->get('/danh-muc/khong-co')->assertNotFound();
    $this->get('/san-pham/khong-co')->assertNotFound();
});

it('danh mục và bộ lọc là link thường, giữ tham số và nối giá trị bằng dấu phẩy', function () {
    $category = T::seed(fn () => Category::factory()->create(['slug' => 'dam']));
    T::seed(fn () => $this->product->categories()->attach($category->id));

    $this->get('/danh-muc/dam')->assertOk()->assertSee('Đầm lụa');
    $this->get('/tim-kiem?brand=lumiere')->assertOk()
        ->assertSee(e(url('/tim-kiem').'?brand=lumiere%2Curbanx'), false)
        ->assertDontSee('Áo thun Urbanx');
});

it('mua hàng bằng form: thêm giỏ → sửa số lượng → áp mã → đặt COD → trang cảm ơn; giỏ được làm mới', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2])->assertRedirect('/gio-hang');
    $this->get('/gio-hang')->assertOk()->assertSee('600.000 ₫');

    $line = (int) collect(app(NativeCart::class)->current()->lines)->first()->id;
    $this->post("/gio-hang/{$line}", ['quantity' => 1])->assertRedirect('/gio-hang');

    $this->get('/thanh-toan')->assertOk()->assertSee('330.000 ₫')->assertSee('name="idempotency_key"', false);

    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => null]);
    $this->post('/thanh-toan', [...($this->checkout)(), 'voucher_code' => 'GIAM10', 'action' => 'quote'])->assertRedirect('/thanh-toan');
    $this->get('/thanh-toan')->assertSee('300.000 ₫');

    $response = $this->post('/thanh-toan', ($this->checkout)(['voucher_code' => 'GIAM10', 'expected_total' => 300_000]));
    $order = Order::query()->withoutGlobalScopes()->sole();
    $response->assertRedirect("/don-hang/{$order->public_id}");

    expect($order->source)->toBe('web')->and((int) $order->total_amount)->toBe(300_000);
    $this->get("/don-hang/{$order->public_id}")->assertOk()->assertSee($order->number)->assertSee('300.000 ₫');
    $this->get('/gio-hang')->assertSee('Giỏ hàng trống');
});

it('lỗi checkout hiện theo trường; tổng thay đổi → quay lại để khách xác nhận', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);

    $this->post('/thanh-toan', ($this->checkout)(['contact' => ['phone' => '12']]))
        ->assertRedirect('/thanh-toan')->assertSessionHasErrors('contact.phone');
    $this->post('/thanh-toan', ($this->checkout)(['expected_total' => 1]))
        ->assertRedirect('/thanh-toan')->assertSessionHasErrors('business');

    expect(Order::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('chuyển khoản: trang cảm ơn có hướng dẫn chuyển khoản; xem đơn cần token trong phiên', function () {
    config(['vani.bank-transfer.account' => ['bank' => 'Vietcombank', 'account_number' => '0123456789', 'account_name' => 'CONG TY VANI']]);
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', ($this->checkout)(['payment_method' => 'manual_bank_transfer']));
    $order = Order::query()->withoutGlobalScopes()->sole();

    $this->get("/don-hang/{$order->public_id}")->assertOk()->assertSee('0123456789')->assertSee($order->number);

    $this->flushSession();
    $this->get("/don-hang/{$order->public_id}")->assertNotFound();
});

it('không chọn size → lỗi form ở PDP; biến thể hết hàng không thêm được', function () {
    $this->from("/san-pham/{$this->product->slug}")->post('/gio-hang', [])->assertRedirect("/san-pham/{$this->product->slug}")->assertSessionHasErrors('variant_id');
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 999])->assertSessionHasErrors('business');
});

describe('theme', function () {
    beforeEach(function () {
        $this->themes = storage_path('framework/testing/themes-'.uniqid());
        File::ensureDirectoryExists($this->themes.'/shop/views/pages');
        File::link(base_path('custom/theme/vani-base'), $this->themes.'/vani-base');
        File::put($this->themes.'/shop/theme.json', json_encode(['label' => 'Shop', 'parent' => 'vani-base', 'tokens' => ['color-primary' => '#0f766e']]));
        File::put($this->themes.'/shop/views/pages/brands.blade.php', "@extends('theme::layouts.app')\n@section('content')<h1>Trang thương hiệu của theme con</h1>@endsection");
        config(['vanishop.storefront.themes_path' => $this->themes]);
        app()->forgetInstance(Themes::class);
    });

    afterEach(fn () => File::deleteDirectory($this->themes));

    it('theme con override vài view, view còn lại theo vani-base; theme cấu hình sai → vani-base', function () {
        T::seed(fn () => app(Settings::class)->set('core', 'theme', 'shop'));

        $this->get('/thuong-hieu')->assertOk()->assertSee('Trang thương hiệu của theme con')->assertSee('--vani-color-primary:#0f766e', false);
        $this->get("/san-pham/{$this->product->slug}")->assertOk()->assertSee('Thêm vào giỏ');

        T::seed(fn () => app(Settings::class)->set('core', 'theme', 'khong-co'));
        $this->get('/thuong-hieu')->assertOk()->assertDontSee('Trang thương hiệu của theme con');
    });

    it('token giao diện cấu hình trong Admin ghi đè theme; giá trị không an toàn bị bỏ', function () {
        T::seed(fn () => app(Settings::class)->set('core', 'theme.tokens', json_encode(['color-accent' => '#be123c', 'radius' => '1rem;}</style><script>'])));

        $this->get('/thuong-hieu')->assertSee('--vani-color-accent:#be123c', false)->assertDontSee('<script>x', false)->assertDontSee('--vani-radius:1rem;}', false);
    });
});

describe('slot storefront', function () {
    it('chỉ nối thêm theo priority; listener lỗi hoặc trả chuỗi thô bị bỏ; plugin tắt thì không hiện', function () {
        $hooks = app(HookManager::class);
        File::ensureDirectoryExists($views = storage_path('framework/testing/slot-views'));
        File::put("{$views}/installment.blade.php", '<p>Trả góp 0% cho {{ $name }}</p>');
        view()->addNamespace('fixture-slot', $views);

        $hooks->onSlot('vani.storefront.pdp.after_price', fn (array $product) => new SlotView('fixture-slot::installment', ['name' => $product['name']]), 20);
        $hooks->onSlot('vani.storefront.pdp.after_price', fn () => new HtmlString('<p>Điểm thưởng dự kiến</p>'), 10);
        $hooks->onSlot('vani.storefront.pdp.after_price', fn () => throw new RuntimeException('plugin hỏng'));
        $hooks->onSlot('vani.storefront.pdp.after_price', fn () => '<script>alert(1)</script>');
        $hooks->onSlot('vani.storefront.pdp.after_price', fn () => new HtmlString('<p>Của plugin đã tắt</p>'), 10, 'vani.not-enabled');

        $html = $this->get("/san-pham/{$this->product->slug}")->assertOk()->getContent();

        expect($html)->toContain('Trả góp 0% cho Đầm lụa')->toContain('Điểm thưởng dự kiến')
            ->not->toContain('alert(1)')->not->toContain('Của plugin đã tắt')
            ->and(strpos($html, 'Điểm thưởng dự kiến'))->toBeLessThan(strpos($html, 'Trả góp 0%'));

        File::deleteDirectory($views);
    });
});
