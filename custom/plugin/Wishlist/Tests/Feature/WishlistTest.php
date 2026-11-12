<?php

use Illuminate\Support\Facades\File;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Events\CustomerAnonymized;
use Modules\Customer\Events\CustomerMerged;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Extension\Application\Plugins\PluginAssets;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Notifier;
use Modules\Pricing\Events\PriceChanged;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\Phones;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Wishlist\Infrastructure\Wishlists;
use Plugin\Wishlist\Persistence\WishlistItem;
use Plugin\Wishlist\WishlistServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../../../modules/Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s, 'brand' => $this->brand, 'location' => $this->location] = C::store();
    $this->style = $this->s->style;
    $this->assets = storage_path('framework/testing/wishlist-assets-'.uniqid());
    app()->instance(PluginAssets::class, new PluginAssets($this->assets, 'https://shop.test/plugins'));
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(WishlistServiceProvider::ID);
        app(PluginManager::class)->enable(WishlistServiceProvider::ID);
    });
    app()->register(WishlistServiceProvider::class);
    $this->signIn = function (string $phone = '0912345678'): int {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => $phone]);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString($phone)->e164.'|login']]);

        return (int) Customer::query()->where('phone', Phones::fromString($phone)->e164)->value('id');
    };
    $this->toggle = fn (int $styleId) => $this->postJson('/p/vani-wishlist/toggle', ['style_id' => $styleId]);
});

afterEach(fn () => File::deleteDirectory($this->assets));

it('trang sản phẩm có nút tim + script của plugin; HTML trang cache giống nhau giữa khách vãng lai và khách đăng nhập', function () {
    config(['vanishop.storefront.page_cache.enabled' => true]);
    $guest = $this->get('/san-pham/'.$this->style->slug)->assertOk()
        ->assertSee('data-vani-wishlist="'.$this->style->id.'"', false)
        ->assertSee('data-vani-wishlist-script', false)
        ->assertSee('https://shop.test/plugins/vani.wishlist/wishlist.', false)
        ->assertSee('data-vani-wishlist-count', false)
        ->getContent();

    ($this->signIn)();
    expect($this->get('/san-pham/'.$this->style->slug)->getContent())->toBe($guest);
});

it('header yêu thích dùng biểu tượng Lucide và vẫn có nhãn trợ năng', function () {
    $this->get('/')->assertOk()
        ->assertSee('data-vani-wishlist-link aria-label="Yêu thích"', false)
        ->assertSee('data-lucide="heart"', false)
        ->assertSee('data-vani-wishlist-count', false);
});

it('khách đăng nhập: thêm/bỏ (JSON và form không JS), trạng thái qua Storefront API ids (phiên cùng nguồn); vãng lai bị từ chối', function () {
    ($this->toggle)($this->style->id)->assertUnauthorized();
    $this->post('/p/vani-wishlist/toggle', ['style_id' => $this->style->id])->assertRedirect(url('/tai-khoan/dang-nhap'));
    $this->getJson('/api/storefront/v1/x/vani-wishlist/ids', ['Sec-Fetch-Site' => 'same-origin'])->assertUnauthorized();

    ($this->signIn)();
    ($this->toggle)($this->style->id)->assertOk()->assertJson(['in_wishlist' => true, 'count' => 1]);
    $this->getJson('/api/storefront/v1/x/vani-wishlist/ids', ['Sec-Fetch-Site' => 'same-origin'])->assertOk()->assertExactJson(['data' => ['ids' => [$this->style->id]]])->assertHeader('Cache-Control', 'no-store, private');
    ($this->toggle)($this->style->id)->assertOk()->assertJson(['in_wishlist' => false, 'count' => 0]);

    $this->from('/san-pham/'.$this->style->slug)->post('/p/vani-wishlist/toggle', ['style_id' => $this->style->id])->assertRedirect('/san-pham/'.$this->style->slug);
    expect(WishlistItem::query()->count())->toBe(1);

    [$hidden] = P::variants(T::product($this->brand->id, ['status' => 'draft']), ['M']);
    ($this->toggle)($hidden->style_id)->assertStatus(422);
});

it('gộp danh sách của trình duyệt khi đăng nhập: bỏ trùng, bỏ mẫu không bán, giữ giới hạn', function () {
    [$other] = P::variants(T::product($this->brand->id, ['name' => 'Áo khác']), ['M']);
    [$draft] = P::variants(T::product($this->brand->id, ['status' => 'draft']), ['M']);
    $customerId = ($this->signIn)();
    app(Wishlists::class)->add($customerId, [$this->style->id]);

    $this->postJson('/api/storefront/v1/x/vani-wishlist/items/merge', ['style_ids' => [$this->style->id, $other->style_id, $draft->style_id]], ['Sec-Fetch-Site' => 'same-origin'])->assertOk()
        ->assertJson(['data' => ['added' => 1, 'ids' => [$other->style_id, $this->style->id]]]);
    // Thêm/bỏ như wishlist.js: POST/DELETE items, phản hồi danh sách mới.
    $this->deleteJson("/api/storefront/v1/x/vani-wishlist/items/{$other->style_id}", [], ['Sec-Fetch-Site' => 'same-origin'])->assertOk()->assertJsonPath('data.ids', [$this->style->id]);
});

it('trang /tai-khoan/yeu-thich (cần đăng nhập) + mục menu tài khoản; bản /en/account/wishlist', function () {
    $this->get('/tai-khoan/yeu-thich')->assertRedirect();

    $customerId = ($this->signIn)();
    $this->get('/tai-khoan/yeu-thich')->assertOk()->assertSee('Chưa có sản phẩm yêu thích')->assertSee('data-account-page="vani.wishlist:wishlist"', false);
    app(Wishlists::class)->add($customerId, [$this->style->id]);
    $this->get('/tai-khoan/yeu-thich')->assertOk()->assertSee('data-vani-wishlist-page', false)->assertSee($this->style->translate('name', 'vi'));
    $this->get('/en/account/wishlist')->assertOk()->assertSee('Wishlist');
});

it('Storefront API (Bearer): danh sách thẻ sản phẩm, thêm, bỏ', function () {
    $headers = H::auth(H::login($this));
    $this->postJson('/api/storefront/v1/x/vani-wishlist/items', ['style_id' => $this->style->id], $headers)->assertCreated()->assertJsonPath('data.ids', [$this->style->id]);
    $this->getJson('/api/storefront/v1/x/vani-wishlist/items', $headers)->assertOk()->assertJsonPath('data.0.product.slug', $this->style->slug);
    $this->deleteJson("/api/storefront/v1/x/vani-wishlist/items/{$this->style->id}", [], $headers)->assertOk()->assertJsonPath('data.ids', []);
    $this->getJson('/api/storefront/v1/x/vani-wishlist/items')->assertUnauthorized();
});

it('báo có hàng lại khi bật cấu hình: marketing, một lần; tắt cấu hình thì chỉ cập nhật trạng thái', function () {
    $sent = [];
    app()->instance(Notifier::class, new class($sent) implements Notifier
    {
        public function __construct(private array &$sent) {}

        public function notify(NotificationRequest $request): array
        {
            $this->sent[] = $request;

            return ['mail'];
        }
    });
    $customerId = app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(Customers::class)->resolveForCheckout('0912345678', 'Lan', 'lan@example.com'));
    app(Wishlists::class)->add($customerId, [$this->style->id]);
    WishlistItem::query()->update(['in_stock' => false]);

    event(new AvailabilityChanged([$this->s->id]));
    expect($sent)->toBe([])->and(WishlistItem::query()->value('in_stock'))->toBeTrue();

    WishlistItem::query()->update(['in_stock' => false]);
    T::seed(fn () => app(Settings::class)->set(WishlistServiceProvider::ID, 'notify_back_in_stock', true));
    event(new AvailabilityChanged([$this->s->id]));
    event(new AvailabilityChanged([$this->s->id]));
    expect($sent)->toHaveCount(1)
        ->and($sent[0]->type)->toBe('wishlist_back_in_stock')
        ->and($sent[0]->category)->toBe(NotificationRequest::MARKETING)
        ->and($sent[0]->variables['product_url'])->toBe(url('/san-pham/'.$this->style->slug));
});

it('báo giảm giá: giá thấp nhất thấp hơn giá lúc thêm', function () {
    $sent = [];
    app()->instance(Notifier::class, new class($sent) implements Notifier
    {
        public function __construct(private array &$sent) {}

        public function notify(NotificationRequest $request): array
        {
            $this->sent[] = $request;

            return ['mail'];
        }
    });
    T::seed(fn () => app(Settings::class)->set(WishlistServiceProvider::ID, 'notify_price_drop', true));
    $customerId = app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(Customers::class)->resolveForCheckout('0912345678', 'Lan', null));
    app(Wishlists::class)->add($customerId, [$this->style->id]);
    expect(WishlistItem::query()->value('price_amount'))->toBe(200_000);

    P::priceList(['code' => 'sale', 'type' => 'sale', 'priority' => 10], [$this->s->id => [150_000]]);
    event(new PriceChanged(1, [$this->s->id]));
    expect($sent)->toHaveCount(1)->and($sent[0]->type)->toBe('wishlist_price_drop')
        ->and(WishlistItem::query()->value('price_amount'))->toBe(150_000);
});

it('gộp hồ sơ khách chuyển danh sách (bỏ trùng); ẩn danh hoá xoá danh sách', function () {
    [$other] = P::variants(T::product($this->brand->id, ['name' => 'Áo khác']), ['M']);
    $wishlists = app(Wishlists::class);
    $wishlists->add(1001, [$this->style->id, $other->style_id]);
    $wishlists->add(1002, [$this->style->id]);

    event(new CustomerMerged(1001, 1002, 0));
    expect($wishlists->ids(1002))->toEqualCanonicalizing([$this->style->id, $other->style_id])->and($wishlists->ids(1001))->toBe([]);

    event(new CustomerAnonymized(1002));
    expect(WishlistItem::query()->count())->toBe(0);
});
