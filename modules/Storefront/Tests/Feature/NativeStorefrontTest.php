<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Modules\Catalog\Application\Products\StyleColorService;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
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
        'shipping_address' => ['province_code' => '29', 'ward_code' => '70101065', 'street_line' => '12 Lê Lợi'],
        'shipping_method' => 'standard',
        'payment_method' => 'cod',
        'expected_total' => 330_000,
        'idempotency_key' => 'native-order-0001',
    ], $overrides);
});

it('render SSR: trang chủ, thương hiệu, tìm kiếm, PDP có giá và form thêm giỏ dùng được khi tắt JS', function () {
    $this->get('/')->assertOk()->assertSee('Đầm lụa')->assertSee('Urbanx')
        ->assertSee('id="vani-cart-drawer"', false)
        ->assertSee('data-vani-drawer-close', false)
        ->assertSee('data-vani-mini-cart-body', false);
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

it('renders reusable drawer content with escaped title and caller-provided attributes', function () {
    view()->addNamespace('theme', base_path('custom/theme/vani-base/views'));

    $html = view('theme::partials.drawer', [
        'id' => 'fixture-drawer',
        'title' => 'Nội dung <an toàn>',
        'contentView' => 'theme::partials.cart-drawer-content',
        'dataAttributes' => ['data-fixture-drawer' => true],
        'closeAttributes' => ['data-fixture-close' => true],
    ])->render();

    expect($html)->toContain('id="fixture-drawer"')
        ->toContain('aria-labelledby="fixture-drawer-title"')
        ->toContain('Nội dung &lt;an toàn&gt;')
        ->toContain('data-vani-drawer-close')
        ->toContain('data-fixture-close')
        ->toContain('data-fixture-drawer')
        ->toContain('data-vani-mini-cart-body');
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

    it('font mặc định của theme: token font-sans in nguyên dấu nháy trong <style> (không &#039;); đổi font thì không nạp Anivele', function () {
        $this->get('/thuong-hieu')->assertOk()->assertSee("--vani-font-sans:'Anivele', ui-sans-serif", false)->assertDontSee('&#039;Anivele', false)
            ->assertSee('anivele-400-normal', false);

        T::seed(fn () => app(Settings::class)->set('core', 'theme.tokens', json_encode(['font-sans' => '"Be Vietnam Pro", sans-serif'])));
        $this->get('/thuong-hieu')->assertOk()->assertSee('--vani-font-sans:"Be Vietnam Pro", sans-serif', false)->assertDontSee('anivele-400-normal', false);
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

it('slot header/footer/checkout mới hiện ở đúng trang', function () {
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.header.actions', fn () => new HtmlString('<a href="/p/wishlist">Yêu thích</a>'));
    $hooks->onSlot('vani.storefront.header.nav', fn () => new HtmlString('<a href="/new">Mới</a>'));
    $hooks->onSlot('vani.storefront.footer.columns', fn () => new HtmlString('<p>Đăng ký nhận tin</p>'));
    $hooks->onSlot('vani.storefront.checkout.address_after', fn () => new HtmlString('<input name="extra[vani.gift][message]">'));

    $this->get('/')
        ->assertSee('Yêu thích')
        ->assertSee('data-slot="vani.storefront.header.nav"', false)
        ->assertSee('Mới')
        ->assertSee('Đăng ký nhận tin');
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->get('/thanh-toan')->assertSee('name="extra[vani.gift][message]"', false);
});

it('theme slots mở rộng layout, trang chủ, trang thương hiệu và danh sách sản phẩm', function () {
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.layout.head', fn () => new HtmlString('<meta name="plugin-verification" content="verified">'));
    $hooks->onSlot('vani.storefront.layout.header.before', fn () => new HtmlString('<p>PLUGIN-HEADER-BEFORE</p>'));
    $hooks->onSlot('vani.storefront.layout.header.after', fn () => new HtmlString('<p>PLUGIN-HEADER-AFTER</p>'));
    $hooks->onSlot('vani.storefront.layout.main.before', fn () => new HtmlString('<p>PLUGIN-MAIN-BEFORE</p>'));
    $hooks->onSlot('vani.storefront.layout.main.after', fn () => new HtmlString('<p>PLUGIN-MAIN-AFTER</p>'));
    $hooks->onSlot('vani.storefront.layout.footer.after', fn () => new HtmlString('<p>PLUGIN-FOOTER-AFTER</p>'));
    $hooks->onSlot('vani.storefront.home.before_blocks', fn () => new HtmlString('<p>PLUGIN-HOME-BEFORE</p>'));
    $hooks->onSlot('vani.storefront.home.after_blocks', fn () => new HtmlString('<p>PLUGIN-HOME-AFTER</p>'));
    $hooks->onSlot('vani.storefront.brands.after_list', fn (array $groups) => new HtmlString('<p>BRAND-GROUPS:'.count($groups).'</p>'));
    $hooks->onSlot('vani.storefront.plp.card.actions', fn (array $product) => new HtmlString('<button>WISHLIST:'.$product['slug'].'</button>'));
    $hooks->onSlot('vani.storefront.plp.after_listing', fn (array $context, array $listing) => new HtmlString('<p>LISTING-END:'.$context['type'].':'.$listing['total'].'</p>'));

    $home = $this->get('/')->assertOk()->getContent();
    $head = substr($home, 0, strpos($home, '</head>'));

    expect($head)->toContain('<meta name="plugin-verification" content="verified">')
        ->not->toContain('data-slot="vani.storefront.layout.head"')
        ->and(strpos($home, 'PLUGIN-HEADER-BEFORE'))->toBeLessThan(strpos($home, '<header'))
        ->and(strpos($home, 'PLUGIN-HEADER-AFTER'))->toBeGreaterThan(strpos($home, '</header>'))
        ->and(strpos($home, 'PLUGIN-MAIN-BEFORE'))->toBeLessThan(strpos($home, 'PLUGIN-HOME-BEFORE'))
        ->and(strpos($home, 'PLUGIN-HOME-AFTER'))->toBeLessThan(strpos($home, 'PLUGIN-MAIN-AFTER'))
        ->and(strpos($home, 'PLUGIN-FOOTER-AFTER'))->toBeGreaterThan(strpos($home, '</footer>'));

    $this->get('/thuong-hieu')->assertSee('BRAND-GROUPS:', false);
    $listing = $this->get('/tim-kiem?q=dam')->assertSee('WISHLIST:'.$this->product->slug, false)
        ->assertSee('LISTING-END:search:', false)->getContent();
    $card = substr($listing, strpos($listing, '<article'), strpos($listing, '</article>') - strpos($listing, '<article'));

    expect(strpos($card, 'WISHLIST:'.$this->product->slug))->toBeGreaterThan(strpos($card, '</a>'));
});

it('theme slots xuất hiện đúng vị trí trên PDP, danh sách rỗng, danh sách thương hiệu và giỏ rỗng', function () {
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.brands.before_list', fn (array $groups) => new HtmlString('<p>BRANDS-BEFORE:'.count($groups).'</p>'));
    $hooks->onSlot('vani.storefront.plp.empty', fn (array $context, array $listing) => new HtmlString('<p>PLP-EMPTY:'.$context['type'].':'.$listing['total'].'</p>'));
    $hooks->onSlot('vani.storefront.cart.empty', fn () => new HtmlString('<p>CART-EMPTY-RECOMMENDATIONS</p>'));
    $hooks->onSlot('vani.storefront.pdp.before_gallery', fn (array $product) => new HtmlString('<p>PDP-BEFORE-GALLERY:'.$product['slug'].'</p>'));
    $hooks->onSlot('vani.storefront.pdp.before_add_to_cart', fn (array $product) => new HtmlString('<p>PDP-BEFORE-PURCHASE:'.$product['slug'].'</p>'));
    $hooks->onSlot('vani.storefront.pdp.after_videos', fn (array $product) => new HtmlString('<p>PDP-AFTER-VIDEOS:'.$product['slug'].'</p>'));
    $hooks->onSlot('vani.storefront.pdp.after_related_products', fn (array $product) => new HtmlString('<p>PDP-AFTER-RELATED:'.$product['slug'].'</p>'));

    $brands = $this->get('/thuong-hieu')->assertSee('BRANDS-BEFORE:')->getContent();
    $listing = $this->get('/tim-kiem?q=no-such-vani-product')->assertSee('PLP-EMPTY:search:0', false)->getContent();
    $cart = $this->get('/gio-hang')->assertSee('CART-EMPTY-RECOMMENDATIONS', false)->getContent();
    $product = $this->get("/san-pham/{$this->product->slug}")
        ->assertSee('PDP-BEFORE-GALLERY:'.$this->product->slug, false)
        ->assertSee('PDP-BEFORE-PURCHASE:'.$this->product->slug, false)
        ->assertSee('PDP-AFTER-VIDEOS:'.$this->product->slug, false)
        ->assertSee('PDP-AFTER-RELATED:'.$this->product->slug, false)
        ->getContent();

    expect(strpos($brands, 'BRANDS-BEFORE:'))->toBeLessThan(strpos($brands, 'data-brand-letters'))
        ->and($listing)->toContain('Không có sản phẩm phù hợp.')
        ->and($cart)->toContain('Giỏ hàng trống.')
        ->and(strpos($product, 'PDP-BEFORE-GALLERY'))->toBeLessThan(strpos($product, 'data-product-gallery'))
        ->and(strpos($product, 'PDP-BEFORE-PURCHASE'))->toBeLessThan(strpos($product, 'data-variant-form'))
        ->and(strpos($product, 'PDP-AFTER-VIDEOS'))->toBeLessThan(strpos($product, 'PDP-AFTER-RELATED'))
        ->and(strpos($product, 'PDP-AFTER-RELATED'))->toBeLessThan(strpos($product, 'data-vani-recently-viewed'));
});

it('order.before_summary nhận đơn và xuất hiện trước tóm tắt ở trang cảm ơn', function () {
    app(HookManager::class)->onSlot(
        'vani.storefront.order.before_summary',
        fn (array $order) => new HtmlString('<p>ORDER-BEFORE-SUMMARY:'.$order['number'].'</p>'),
    );
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', ($this->checkout)())->assertRedirect();
    $order = Order::query()->withoutGlobalScopes()->sole();

    $html = $this->get("/don-hang/{$order->public_id}")
        ->assertSee('ORDER-BEFORE-SUMMARY:'.$order->number, false)
        ->getContent();

    expect(strpos($html, 'ORDER-BEFORE-SUMMARY'))->toBeLessThan(strpos($html, '330.000 ₫'));
});

it('giỏ và checkout cho phép plugin chèn nội dung quanh các tóm tắt', function () {
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.cart.before_lines', fn (array $cart) => new HtmlString('<p>CART-LINES:'.count($cart['lines']).'</p>'));
    $hooks->onSlot('vani.storefront.cart.after_summary', fn () => new HtmlString('<p>CART-SUMMARY-END</p>'));
    $hooks->onSlot('vani.storefront.checkout.order_summary', fn (array $quote) => new HtmlString('<p>CHECKOUT-LINES:'.count($quote['lines']).'</p>'));
    $hooks->onSlot('vani.storefront.checkout.after_summary', fn (array $quote) => new HtmlString('<p>CHECKOUT-SUMMARY:'.$quote['total']['amount'].'</p>'));

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/gio-hang')->assertSee('CART-LINES:1')->assertSee('CART-SUMMARY-END');
    $this->get('/thanh-toan')->assertSee('CHECKOUT-LINES:1')->assertSee('CHECKOUT-SUMMARY:330000');
});

it('robots.txt chặn trang riêng tư, không lộ đường dẫn Admin; sitemap gồm danh mục, thương hiệu, sản phẩm đang hiển thị', function () {
    $robots = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();
    expect($robots)->toContain('Disallow: /tai-khoan')->toContain('Sitemap: '.url('/sitemap.xml'))->not->toContain('/admin');

    T::product($this->urbanx->id, ['name' => 'Nháp', 'slug' => 'ban-nhap', 'status' => 'draft']);
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($xml)->toContain(route('storefront.product', $this->product->slug))
        ->toContain(route('storefront.brand', 'urbanx'))
        ->not->toContain('ban-nhap')
        ->and(simplexml_load_string($xml))->not->toBeFalse();
});

it('PDP có ảnh: gallery (Swiper/PhotoSwipe) — ảnh chính + ảnh sau tải lười, liên kết phóng to, thumbnail, data-images cho JS đổi theo màu (hồi quy htmlspecialchars array)', function () {
    Storage::fake('public');
    $styleColor = $this->s->styleColor;
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StyleColorService::class)->addImages($this->product, $styleColor, [
        UploadedFile::fake()->image('truoc.jpg', 300, 400),
        UploadedFile::fake()->image('sau.jpg', 310, 410),
    ]));
    $urls = $styleColor->gallery()->with('media')->orderBy('position')->get()->map(fn ($image) => $image->media->url(800))->all();

    $html = $this->get("/san-pham/{$this->product->slug}")->assertOk()
        ->assertSee('data-gallery-main src="'.e($urls[0]).'"', false)
        ->assertSee('data-gallery-thumb data-src="'.e($urls[1]).'"', false)
        ->getContent();
    expect(substr_count($html, 'data-gallery-thumb '))->toBe(2)
        ->and(substr_count($html, 'data-gallery-slide>'))->toBe(2)
        ->and($html)->toContain('data-images=\'[{"url":')
        ->toContain('data-gallery-image src="'.e($urls[1]).'"')
        ->toContain('data-pswp-width=')
        // Chỉ ảnh đầu ưu tiên cao; không JS vẫn xem được (dải cuộn, liên kết mở ảnh lớn).
        ->and(substr_count($html, 'fetchpriority="high"'))->toBe(1);
});

it('slot đầu trang và đầu danh sách (0.3.43): body_start trước header, before_listing nhận ngữ cảnh danh mục/thương hiệu/tìm kiếm', function () {
    $hooks = app(HookManager::class);
    $hooks->onSlot('vani.storefront.layout.body_start', fn () => new HtmlString('<div>THÔNG-BÁO</div>'));
    $hooks->onSlot('vani.storefront.plp.before_listing', fn (array $context, array $listing) => new HtmlString('<p>NGỮ-CẢNH:'.$context['type'].':'.($context['category']['slug'] ?? $context['brand']['slug'] ?? '-').':'.$listing['total'].'</p>'));

    $home = $this->get('/')->assertOk()->getContent();
    expect(strpos($home, 'THÔNG-BÁO'))->toBeLessThan(strpos($home, '<header'));

    $category = T::seed(fn () => Category::factory()->create(['slug' => 'dam-test']));
    $this->get('/danh-muc/dam-test')->assertOk()->assertSee('NGỮ-CẢNH:category:dam-test:0', false);
    $this->get("/thuong-hieu/{$this->urbanx->slug}")->assertOk()->assertSee("NGỮ-CẢNH:brand:{$this->urbanx->slug}:1", false);
    $this->get('/tim-kiem?q=thun')->assertOk()->assertSee('NGỮ-CẢNH:search:-:1', false);
});

it('thay đổi giỏ → trang kế tiếp có dữ liệu cho sự kiện vani:cart-updated, chỉ một lần (0.3.43)', function () {
    $change = function (): ?array {
        $html = $this->get('/gio-hang')->assertOk()->getContent();

        return preg_match('/<script type="application\/json" data-vani-cart-change>(.*?)<\/script>/s', $html, $m) === 1 ? json_decode($m[1], true) : null;
    };

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2]);
    expect($change())->toMatchArray(['action' => 'add', 'variant_id' => $this->s->id, 'quantity' => 2, 'unit_amount' => 300_000, 'currency' => 'VND'])
        ->and($change())->toBeNull(); // flash một lần

    $line = (int) collect(app(NativeCart::class)->current()->lines)->first()->id;
    $this->post("/gio-hang/{$line}", ['quantity' => 5]);
    expect($change())->toMatchArray(['action' => 'add', 'quantity' => 3]);
    $this->post("/gio-hang/{$line}", ['quantity' => 1]);
    expect($change())->toMatchArray(['action' => 'remove', 'quantity' => 4]);
    $this->post("/gio-hang/{$line}", ['quantity' => 1]);
    expect($change())->toBeNull(); // không đổi số lượng

    $this->post("/gio-hang/{$line}/xoa");
    expect($this->getJson('/_vani/phien')->json('cart_change'))->toMatchArray(['action' => 'remove', 'variant_id' => $this->s->id, 'quantity' => 1]);
});
