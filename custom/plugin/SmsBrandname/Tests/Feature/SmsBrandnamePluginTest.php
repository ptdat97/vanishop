<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Persistence\Models\NotificationLog;
use Modules\Notification\Persistence\Models\NotificationTemplate;
use Modules\Shared\Domain\Text\VietnameseText;
use Modules\Tenancy\Contracts\Settings;
use Plugin\SmsBrandname\Infrastructure\SmsChannel;
use Plugin\SmsBrandname\SmsBrandnameServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

function enableSmsBrandname(): void
{
    config([
        'vani.sms-brandname.api_key' => 'key', 'vani.sms-brandname.secret_key' => 'secret',
        'vani.sms-brandname.brandname' => 'VANISHOP',
    ]);
    T::seed(function () {
        app(PluginManager::class)->install('vani.sms-brandname');
        app(PluginManager::class)->enable('vani.sms-brandname');
    });
    app()->register(SmsBrandnameServiceProvider::class);
    app(PluginActivation::class)->flush();
}

function smsMessage(string $body = 'Đơn LU-01 đã giao!'): OutgoingMessage
{
    return new OutgoingMessage(7, 'order_placed:1:sms', 'order_placed', new Recipient(phone: '+84912345678'), null, $body, [], 1);
}

beforeEach(function () {
    ['s' => $this->s] = C::store();
    enableSmsBrandname();
});

it('gửi qua eSMS: số trong nước, nội dung bỏ dấu, brandname của cửa hàng, RequestId = khoá idempotency', function () {
    Http::fake(['rest.esms.vn/*' => Http::response(['CodeResult' => '100', 'SMSID' => 'abc-1'])]);

    $result = app(SmsChannel::class)->send(smsMessage());

    expect($result->isSent())->toBeTrue()->and($result->providerMessageId)->toBe('abc-1');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/SendMultipleMessage_V4_post_json/')
        && $request['Phone'] === '0912345678'
        && $request['Content'] === 'Don LU-01 da giao!'
        && $request['Brandname'] === 'VANISHOP'
        && $request['SmsType'] === '2'
        && $request['RequestId'] === 'order_placed:1:sms');
});

it('phân loại lỗi: 100 ok; 103/5xx/timeout thử lại; mã khác vĩnh viễn; thiếu cấu hình vĩnh viễn', function (mixed $response, string $kind) {
    Http::fake(['rest.esms.vn/*' => $response]);

    expect(app(SmsChannel::class)->send(smsMessage())->kind)->toBe($kind);
})->with([
    'hết tiền' => [fn () => Http::response(['CodeResult' => '103', 'ErrorMessage' => 'Balance not enough']), 'retryable'],
    'số sai' => [fn () => Http::response(['CodeResult' => '118', 'ErrorMessage' => 'Invalid phone']), 'permanent'],
    '5xx' => [fn () => Http::response('down', 502), 'retryable'],
    'timeout' => [fn () => fn () => throw new ConnectionException('timeout'), 'retryable'],
]);

it('plugin bật → tin giao dịch có mẫu kênh sms được gửi qua eSMS, có nhật ký', function () {
    Http::fake(['rest.esms.vn/*' => Http::response(['CodeResult' => '100', 'SMSID' => 'sms-9'])]);
    T::seed(fn () => NotificationTemplate::query()->create(['type' => 'order_placed', 'channel' => 'sms', 'body' => '{{ store_name }}: da nhan don {{ order_number }}']));

    $headers = [];
    $created = $this->postJson('/api/storefront/v1/carts', [], $headers);
    $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $number = $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'sms-order-1'])
        ->assertCreated()->json('data.number');

    $log = NotificationLog::query()->where('channel', 'sms')->sole();
    expect($log->status)->toBe('sent')->and($log->provider_message_id)->toBe('sms-9');
    Http::assertSent(fn (Request $request): bool => $request['Content'] === VietnameseText::stripDiacritics(config('app.name').": da nhan don {$number}"));
});

it('brandname đặt trong Admin → Cấu hình ghi đè config', function () {
    Http::fake(['rest.esms.vn/*' => Http::response(['CodeResult' => '100', 'SMSID' => 'x'])]);
    T::seed(fn () => app(Settings::class)->set('vani.sms-brandname', 'brandname', 'VANI.VN'));

    app(SmsChannel::class)->send(smsMessage());

    Http::assertSent(fn (Request $request): bool => $request['Brandname'] === 'VANI.VN');
});
