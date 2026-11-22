<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Contracts\PhoneOtpLogin;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Plugin\ZaloZns\Infrastructure\ZnsChannel;
use Plugin\ZaloZns\ZaloZnsServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/** Kênh OTP dự phòng có priority thấp hơn ZNS (60), thay cho plugin SMS. */
final class ZaloZnsFallbackOtpSender implements OtpSender
{
    /** @var list<string> */
    public static array $sent = [];

    public function channel(): string
    {
        return 'sms';
    }

    public function priority(): int
    {
        return 50;
    }

    public function isAvailable(CustomerContact $contact): bool
    {
        return true;
    }

    public function send(CustomerContact $contact, string $code, OtpPurpose $purpose): void
    {
        self::$sent[] = $contact->phone;
    }
}

function enablePlugin(string $id, string $provider): void
{
    T::seed(function () use ($id) {
        app(PluginManager::class)->install($id);
        app(PluginManager::class)->enable($id, 'owner', null);
    });
    app()->register($provider);
    app(PluginActivation::class)->flush();
}

function znsMessage(array $meta): OutgoingMessage
{
    return new OutgoingMessage(42, 'order_placed:1:zns', 'order_placed', new Recipient(phone: '+84912345678'), null, null, $meta, 1);
}

beforeEach(function () {
    C::store();
    config([
        'vani.zalo-zns.app_id' => 'app', 'vani.zalo-zns.app_secret' => 'app-secret', 'vani.zalo-zns.refresh_token' => 'refresh-0',
        'vani.zalo-zns.otp_template_id' => 'OTP-TPL',
    ]);
    enablePlugin('vani.zalo-zns', ZaloZnsServiceProvider::class);
    $this->oauth = fn (int $n) => Http::response(['access_token' => "access-{$n}", 'refresh_token' => "refresh-{$n}", 'expires_in' => 90000]);
});

it('làm mới access token bằng refresh token (lưu refresh token mới), rồi gửi template với tham số đã render', function () {
    Http::fake([
        'oauth.zaloapp.com/*' => ($this->oauth)(1),
        'business.openapi.zalo.me/*' => Http::response(['error' => 0, 'message' => 'Success', 'data' => ['msg_id' => 'zns-1']]),
    ]);

    $result = app(ZnsChannel::class)->send(znsMessage(['template_id' => 'TPL-1', 'params' => ['order_code' => 'LU-01', 'total' => 330000]]));
    app(ZnsChannel::class)->send(znsMessage(['template_id' => 'TPL-1', 'params' => []]));

    expect($result->isSent())->toBeTrue()->and($result->providerMessageId)->toBe('zns-1')
        ->and(Cache::get('vani.zalo-zns.refresh_token'))->toBe('refresh-1');
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v4/oa/access_token')
        && $request['refresh_token'] === 'refresh-0' && $request->header('secret_key')[0] === 'app-secret');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/message/template')
        && $request->header('access_token')[0] === 'access-1'
        && $request['phone'] === '84912345678'
        && $request['template_data'] === ['order_code' => 'LU-01', 'total' => '330000']
        && $request['tracking_id'] === 'vani-42');
    expect(collect(Http::recorded())->filter(fn (array $pair): bool => str_contains($pair[0]->url(), 'access_token'))->count())->toBe(1);
});

it('token hết hạn giữa chừng (-124) → làm mới và gửi lại một lần', function () {
    Cache::put('vani.zalo-zns.access_token', 'stale', 3600);
    Http::fake([
        'oauth.zaloapp.com/*' => ($this->oauth)(2),
        'business.openapi.zalo.me/*' => Http::sequence()
            ->push(['error' => -124, 'message' => 'Access token is invalid'])
            ->push(['error' => 0, 'data' => ['msg_id' => 'zns-2']]),
    ]);

    expect(app(ZnsChannel::class)->send(znsMessage(['template_id' => 'TPL-1']))->providerMessageId)->toBe('zns-2');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/message/template') && $request->header('access_token')[0] === 'access-2');
});

it('thiếu template_id hoặc lỗi dữ liệu → vĩnh viễn; 5xx → thử lại', function () {
    Cache::put('vani.zalo-zns.access_token', 'ok', 3600);
    Http::fake(['business.openapi.zalo.me/*' => Http::sequence()->push(['error' => -108, 'message' => 'Phone number invalid'])->push('down', 503)]);

    expect(app(ZnsChannel::class)->send(znsMessage([]))->error)->toBe('zns.template_missing')
        ->and(app(ZnsChannel::class)->send(znsMessage(['template_id' => 'T']))->kind)->toBe('permanent')
        ->and(app(ZnsChannel::class)->send(znsMessage(['template_id' => 'T']))->kind)->toBe('retryable');
});

it('OTP: ưu tiên ZNS; SĐT không dùng Zalo → Core tự chuyển sang kênh kế tiếp', function () {
    ZaloZnsFallbackOtpSender::$sent = [];
    app(Extensions::class)->tag([ZaloZnsFallbackOtpSender::class], OtpSender::TAG);
    Cache::put('vani.zalo-zns.access_token', 'ok', 3600);
    Http::fake([
        'business.openapi.zalo.me/*' => Http::sequence()->push(['error' => 0, 'data' => ['msg_id' => 'otp-1']])->push(['error' => -118, 'message' => 'Zalo account not existed']),
    ]);

    // Đăng nhập OTP là plugin `vani.phone-otp-login`; kênh gửi mã vẫn qua khung OtpSender của Core.
    expect(app(PhoneOtpLogin::class)->requestCode('0912345678', '127.0.0.1')['channel'])->toBe('zns');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/message/template') && $request['template_id'] === 'OTP-TPL' && preg_match('/^\d{6}$/', $request['template_data']['otp']) === 1);

    expect(app(PhoneOtpLogin::class)->requestCode('0987654321', '127.0.0.1')['channel'])->toBe('sms')
        ->and(ZaloZnsFallbackOtpSender::$sent)->toHaveCount(1);
});
