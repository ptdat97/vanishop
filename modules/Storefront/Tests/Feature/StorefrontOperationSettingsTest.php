<?php

use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Support\Phones;
use Modules\Storefront\Application\Maintenance;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| reference-parity §3.2 (R1): cấu hình vận hành storefront — bảo trì (`storefront.maintenance`) và khách vãng lai
| (`storefront.guest_checkout`). IPN khi bảo trì: custom/plugin/VnPay/Tests/Feature/VnPayGatewayTest.php.
*/

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    $this->settings = app(Settings::class);
});

describe('bảo trì', function () {
    beforeEach(function () {
        $this->settings->set('core', 'storefront.maintenance', true);
    });

    it('trang storefront (kể cả trang cache được) và Storefront API trả 503 + Retry-After, không cache', function (string $path) {
        $path = str_replace('{product}', '/san-pham/'.$this->s->style->slug, $path);

        $this->get($path)->assertStatus(503)
            ->assertHeader('Retry-After', '600')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Cửa hàng đang bảo trì');
    })->with(['trang chủ' => '/', 'sản phẩm' => '{product}', 'giỏ' => '/gio-hang', 'đăng nhập' => '/tai-khoan/dang-nhap']);

    it('Storefront API: lỗi có mã, nội dung theo cấu hình', function () {
        $this->settings->set('core', 'storefront.maintenance_message', 'Nghỉ Tết, mở lại mùng 6.');

        $this->getJson('/api/storefront/v1/products')->assertStatus(503)->assertHeader('Retry-After', '600')
            ->assertJsonPath('error.code', 'storefront.maintenance')
            ->assertJsonPath('error.message', 'Nghỉ Tết, mở lại mùng 6.');
        $this->get('/')->assertStatus(503)->assertSee('Nghỉ Tết, mở lại mùng 6.');
    });

    it('robots.txt, sitemap và Admin không bị chặn', function () {
        $this->get('/robots.txt')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
        $this->get(route('admin.login'))->assertOk();
    });

    it('nhân viên xem trước qua Admin: cookie có chữ ký, xem bình thường nhưng không cache công khai; có audit', function () {
        $staff = StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create();
        $this->actingAs($staff, 'staff')->get(route('admin.storefront.home-blocks.edit'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('previewUrl', route('admin.storefront.maintenance.preview')));

        $cookie = collect($this->get(route('admin.storefront.maintenance.preview'))->assertRedirect(route('storefront.home'))->headers->getCookies())
            ->firstOrFail(fn ($cookie): bool => $cookie->getName() === Maintenance::COOKIE);
        expect($cookie->getPath())->toBe('/')->and($cookie->isHttpOnly())->toBeTrue()
            ->and(AuditLog::query()->where('action', 'storefront.maintenance_preview')->exists())->toBeTrue();

        $this->withUnencryptedCookie(Maintenance::COOKIE, $cookie->getValue())->get('/')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->withCredentials()->withUnencryptedCookie(Maintenance::COOKIE, $cookie->getValue())->getJson('/api/storefront/v1/products')->assertOk();

        Carbon::setTestNow(now()->addHours(Maintenance::BYPASS_HOURS)->addMinute());
        $this->withUnencryptedCookie(Maintenance::COOKIE, $cookie->getValue())->get('/')->assertStatus(503);
        Carbon::setTestNow();
    });

    it('cookie giả hoặc sửa hạn không qua được', function () {
        $forged = (now()->addDay()->getTimestamp()).'.'.str_repeat('a', 64);

        $this->withUnencryptedCookie(Maintenance::COOKIE, $forged)->get('/')->assertStatus(503);
        $this->withUnencryptedCookie(Maintenance::COOKIE, 'rac')->get('/')->assertStatus(503);
    });

    it('xem trước cần quyền storefront.manage', function () {
        $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff')
            ->get(route('admin.storefront.maintenance.preview'))->assertForbidden();
    });

    it('tắt bảo trì → trang công khai cache lại bình thường', function () {
        $this->settings->set('core', 'storefront.maintenance', false);

        $this->get('/')->assertOk()->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300, stale-while-revalidate=600');
    });
});

describe('khách vãng lai', function () {
    beforeEach(function () {
        $this->settings->set('core', 'storefront.guest_checkout', false);
        $this->apiCart = function (array $headers = []): array {
            $created = $this->postJson('/api/storefront/v1/carts', [], $headers)->assertCreated();
            $headers = [...$headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
            $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

            return [$created->json('data.id'), $headers];
        };
    });

    it('API: khách chưa đăng nhập vẫn thêm giỏ, báo giá được nhưng đặt hàng → 403 checkout.sign_in_required', function () {
        [$cart, $headers] = ($this->apiCart)();

        $this->postJson("/api/storefront/v1/checkout/{$cart}/quote", C::orderPayload(), $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'guest-off-0001'])
            ->assertForbidden()->assertJsonPath('error.code', 'checkout.sign_in_required');
    });

    it('API: khách đã đăng nhập đặt được hàng', function () {
        [$cart, $headers] = ($this->apiCart)(H::auth(H::login($this)));

        $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'guest-off-0002'])
            ->assertCreated();
    });

    it('web: trang thanh toán chuyển sang đăng nhập, đăng nhập xong quay lại thanh toán', function () {
        $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1])->assertRedirect();

        $this->get('/thanh-toan')->assertRedirect(route('storefront.account.login'))->assertSessionHas('status', 'Vui lòng đăng nhập để đặt hàng.');
        $this->post('/thanh-toan', ['expected_total' => 330_000, 'idempotency_key' => 'guest-off-web-0001'])->assertRedirect(route('storefront.account.login'));

        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678']);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString('0912345678')->e164.'|login']])
            ->assertRedirect(route('storefront.checkout'));
        $this->get('/thanh-toan')->assertOk();
    });

    it('mặc định (chưa cấu hình) vẫn cho khách vãng lai đặt hàng', function () {
        $this->settings->forget('core', 'storefront.guest_checkout');

        H::guestOrder($this, $this->s->id, 'guest-on-0001');
    });
});
