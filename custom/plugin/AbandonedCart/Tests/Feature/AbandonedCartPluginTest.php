<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as Checkout;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Plugin\AbandonedCart\AbandonedCartServiceProvider;

require_once dirname(__DIR__, 5).'/modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once dirname(__DIR__, 5).'/modules/Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    Catalog::seed(function () {
        app(PluginManager::class)->install(AbandonedCartServiceProvider::ID);
        app(PluginManager::class)->enable(AbandonedCartServiceProvider::ID);
    });
    app()->register(AbandonedCartServiceProvider::class);
    app(PluginActivation::class)->flush();
    H::fakeOtp();
    ['s' => $this->sku] = Checkout::store();

    /** Khách đăng nhập thêm 2 món vào giỏ, giỏ không hoạt động 2 giờ → chạy lệnh phát hiện giỏ bỏ quên của Core. */
    $this->abandon = function (int $customerId): void {
        $email = (string) DB::table('customers')->where('id', $customerId)->value('email');
        $this->post('/tai-khoan/dang-nhap', ['email' => $email, 'password' => H::PASSWORD])->assertRedirect('/tai-khoan');
        $this->post('/gio-hang', ['variant_id' => $this->sku->id, 'quantity' => 2])->assertRedirect('/gio-hang');
        $this->post('/tai-khoan/dang-xuat');
        DB::table('carts')->where('customer_id', $customerId)->update(['last_activity_at' => now()->subHours(2)]);
        $this->artisan('vani:cart:detect-abandoned')->assertSuccessful();
    };
    $this->logs = fn (): array => DB::table('notification_logs')->where('type', 'cart_abandoned')->orderBy('id')->get(['customer_id', 'status', 'error', 'subject', 'body'])->all();
});

it('khách đã đồng ý nhận ưu đãi qua email nhận một email nhắc giỏ, có link giỏ và link huỷ nhận tin', function () {
    $customer = H::account('0912345678');
    app(CustomerAccounts::class)->setMarketingConsent($customer->id, 'email', true, 'test');

    ($this->abandon)($customer->id);
    $this->artisan('vani:cart:detect-abandoned')->assertSuccessful(); // cùng đợt không hoạt động: không phát lại

    $logs = ($this->logs)();
    expect($logs)->toHaveCount(1)
        ->and($logs[0]->status)->not->toBe('skipped')
        ->and($logs[0]->subject)->toBe('Bạn còn sản phẩm trong giỏ hàng')
        ->and($logs[0]->body)->toContain('còn 2 sản phẩm')->toContain(url('/gio-hang'))
        ->toContain('Huỷ nhận tin tại: '.url('/tai-khoan/huy-nhan-tin/'.$customer->public_id.'/email'));
});

it('khách chưa đồng ý: Core ghi nhật ký bỏ qua, không gửi', function () {
    $customer = H::account('0912345679');

    ($this->abandon)($customer->id);

    $logs = ($this->logs)();
    expect($logs)->toHaveCount(1)->and($logs[0]->status)->toBe('skipped')->and($logs[0]->error)->toBe('consent.missing');
});

it('plugin tắt thì không gửi gì', function () {
    Catalog::seed(fn () => app(PluginManager::class)->disable(AbandonedCartServiceProvider::ID));
    app(PluginActivation::class)->flush();
    $customer = H::account('0912345670');
    app(CustomerAccounts::class)->setMarketingConsent($customer->id, 'email', true, 'test');

    ($this->abandon)($customer->id);

    expect(($this->logs)())->toBe([]);
});
