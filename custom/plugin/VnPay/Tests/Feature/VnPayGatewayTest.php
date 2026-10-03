<?php

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Plugin\VnPay\Infrastructure\VnPayGateway;
use Plugin\VnPay\Infrastructure\VnPaySigner;
use Plugin\VnPay\VnPayServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

const VNPAY_SECRET = 'TESTSECRETVNPAY0123456789ABCDEF';
const VNPAY_API = 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';

function configureVnPay(): void
{
    config(['vani.vnpay.tmn_code' => 'VANITEST', 'vani.vnpay.hash_secret' => VNPAY_SECRET, 'vani.vnpay.sandbox' => true, 'vani.vnpay.ttl' => 900]);
}

/**
 * Tham số IPN/Return URL VNPay ký đúng (hoặc sửa sau khi ký).
 *
 * @return array<string, string>
 */
function vnpayParams(string $txnRef, int $amount, string $responseCode = '00', string $transactionNo = '14012345', array $tamper = []): array
{
    $params = [
        'vnp_Amount' => (string) ($amount * 100), 'vnp_BankCode' => 'NCB', 'vnp_CardType' => 'ATM', 'vnp_OrderInfo' => 'Thanh toan don hang',
        'vnp_PayDate' => '20261003101500', 'vnp_ResponseCode' => $responseCode, 'vnp_TmnCode' => 'VANITEST', 'vnp_TransactionNo' => $transactionNo,
        'vnp_TransactionStatus' => $responseCode, 'vnp_TxnRef' => $txnRef,
    ];
    $params['vnp_SecureHash'] = (new VnPaySigner(VNPAY_SECRET))->signQuery($params);

    return [...$params, ...$tamper];
}

/**
 * Phản hồi API giao dịch VNPay có chữ ký đúng.
 *
 * @param  list<string>  $fields
 * @param  array<string, string>  $values
 * @return array<string, string>
 */
function vnpayApiResponse(array $fields, array $values): array
{
    $values = [...array_fill_keys($fields, ''), ...$values];
    $values['vnp_SecureHash'] = (new VnPaySigner(VNPAY_SECRET))->signPipe(array_map(fn (string $field): string => $values[$field], $fields));

    return $values;
}

function vnpayQueryResponse(string $txnRef, string $status = '00', int $amount = 330_000): array
{
    return vnpayApiResponse(
        ['vnp_ResponseId', 'vnp_Command', 'vnp_ResponseCode', 'vnp_Message', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_Amount', 'vnp_BankCode', 'vnp_PayDate', 'vnp_TransactionNo', 'vnp_TransactionType', 'vnp_TransactionStatus', 'vnp_OrderInfo', 'vnp_PromotionCode', 'vnp_PromotionAmount'],
        ['vnp_ResponseId' => 'r1', 'vnp_Command' => 'querydr', 'vnp_ResponseCode' => '00', 'vnp_Message' => 'OK', 'vnp_TmnCode' => 'VANITEST', 'vnp_TxnRef' => $txnRef,
            'vnp_Amount' => (string) ($amount * 100), 'vnp_TransactionNo' => '14012345', 'vnp_TransactionType' => '01', 'vnp_TransactionStatus' => $status],
    );
}

function vnpayRefundResponse(string $txnRef, string $code = '00'): array
{
    return vnpayApiResponse(
        ['vnp_ResponseId', 'vnp_Command', 'vnp_ResponseCode', 'vnp_Message', 'vnp_TmnCode', 'vnp_TxnRef', 'vnp_Amount', 'vnp_BankCode', 'vnp_PayDate', 'vnp_TransactionNo', 'vnp_TransactionType', 'vnp_TransactionStatus', 'vnp_OrderInfo'],
        ['vnp_ResponseId' => 'r2', 'vnp_Command' => 'refund', 'vnp_ResponseCode' => $code, 'vnp_Message' => 'OK', 'vnp_TmnCode' => 'VANITEST', 'vnp_TxnRef' => $txnRef, 'vnp_TransactionNo' => '14099999', 'vnp_TransactionType' => '02'],
    );
}

/**
 * Fake đúng endpoint API giao dịch (đăng ký một lần mỗi test): querydr / refund trả theo trạng thái hiện tại.
 *
 * @param  array{query?: string, refund?: string, tamper?: bool}  $state
 */
function fakeVnPayApi(array $state = []): void
{
    $GLOBALS['vnpay_api_state'] = [...['query' => '00', 'refund' => '00', 'tamper' => false], ...$state];
    if ($GLOBALS['vnpay_api_faked'] ?? false) {
        return;
    }
    $GLOBALS['vnpay_api_faked'] = true;
    Http::preventStrayRequests();
    Http::fake([VNPAY_API => function (HttpRequest $request) {
        $state = $GLOBALS['vnpay_api_state'];
        $body = $request['vnp_Command'] === 'refund'
            ? vnpayRefundResponse((string) $request['vnp_TxnRef'], $state['refund'])
            : vnpayQueryResponse((string) $request['vnp_TxnRef'], $state['query']);

        return Http::response($state['tamper'] ? [...$body, 'vnp_SecureHash' => 'sai'] : $body);
    }]);
}

function installVnPay(): void
{
    configureVnPay();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(VnPayServiceProvider::ID);
        app(PluginManager::class)->enable(VnPayServiceProvider::ID);
    });
    app()->register(VnPayServiceProvider::class);
}

beforeEach(fn () => $GLOBALS['vnpay_api_faked'] = false);

PaymentGatewayContract::define(
    'vani.vnpay',
    function (): VnPayGateway {
        installVnPay();
        fakeVnPayApi();

        return app(VnPayGateway::class);
    },
    validCallback: fn (PaymentData $payment) => Request::create('/api/payments/vnpay/callback', 'GET', vnpayParams($payment->publicId, $payment->amount->amount)),
    tamperedCallback: fn (PaymentData $payment) => Request::create('/api/payments/vnpay/callback', 'GET', vnpayParams($payment->publicId, $payment->amount->amount, tamper: ['vnp_Amount' => '100'])),
);

describe('vani.vnpay', function () {
    beforeEach(function () {
        installVnPay();

        ['s' => $this->s] = C::store();
        $this->order = function (int $quantity = 1, string $key = 'vnpay-order-0001') {
            $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
            $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
            $cart = $created->json('data.id');
            $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => $quantity], $headers)->assertOk();

            return $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'vnpay', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => $key])->assertCreated();
        };
        $this->ipn = fn (array $params) => $this->getJson('/api/payments/vnpay/callback?'.http_build_query($params));
    });

    it('đặt hàng → URL VNPay ký đúng (số tiền ×100, TxnRef = payment, hạn thanh toán); IPN thành công → đơn được xử lý', function () {
        $this->travelTo(new DateTimeImmutable('2026-10-03T03:00:00Z'));
        $payment = ($this->order)()->json('data.payment');
        expect($payment['action']['type'])->toBe('redirect');

        parse_str((string) parse_url($payment['action']['url'], PHP_URL_QUERY), $query);
        expect(strtok($payment['action']['url'], '?'))->toBe('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html')
            ->and($query)->toMatchArray(['vnp_Amount' => '33000000', 'vnp_TxnRef' => $payment['id'], 'vnp_TmnCode' => 'VANITEST', 'vnp_CreateDate' => '20261003100000', 'vnp_ExpireDate' => '20261003101500', 'vnp_ReturnUrl' => url('/p/vani-vnpay/return')])
            ->and((new VnPaySigner(VNPAY_SECRET))->verifyQuery($query))->toBeTrue()
            ->and(Payment::query()->sole()->gateway_reference)->toBe("VNP-{$payment['id']}-20261003100000");

        ($this->ipn)(vnpayParams($payment['id'], 330_000))->assertOk()->assertExactJson(['RspCode' => '00', 'Message' => 'Confirm Success']);
        expect(Payment::query()->sole()->status->value)->toBe('paid')
            ->and(Order::query()->withoutGlobalScopes()->sole()->order_status->value)->toBe('processing'); // xác nhận → tự tạo vận đơn

        // VNPay gửi lại cùng IPN → 02 (đã xác nhận), không ghi nhận lần hai.
        ($this->ipn)(vnpayParams($payment['id'], 330_000))->assertOk()->assertExactJson(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        expect(DB::table('payment_transactions')->where('type', 'callback')->count())->toBe(1);
    });

    it('IPN luôn HTTP 200 kèm RspCode: sai chữ ký 97, không thấy đơn 01, sai số tiền 04; khách huỷ (24) → thanh toán thất bại', function () {
        $payment = ($this->order)()->json('data.payment');

        ($this->ipn)(vnpayParams($payment['id'], 330_000, tamper: ['vnp_Amount' => '100']))->assertOk()->assertJsonPath('RspCode', '97');
        ($this->ipn)(vnpayParams('01JUNKNOWNPAYMENT000000000', 330_000))->assertOk()->assertJsonPath('RspCode', '01');
        ($this->ipn)(vnpayParams($payment['id'], 1_000, transactionNo: '14000001'))->assertOk()->assertJsonPath('RspCode', '04');
        expect(Payment::query()->sole()->status->value)->toBe('pending');

        ($this->ipn)(vnpayParams($payment['id'], 330_000, responseCode: '24', transactionNo: '0'))->assertOk()->assertJsonPath('RspCode', '00');
        expect(Payment::query()->sole()->status->value)->toBe('failed');
    });

    it('Return URL chỉ báo kết quả (không ghi nhận) rồi về trang đơn; chữ ký sai → trang chủ báo lỗi', function () {
        $payment = ($this->order)()->json('data.payment');

        $this->get('/p/vani-vnpay/return?'.http_build_query(vnpayParams($payment['id'], 330_000)))
            ->assertRedirect(route('storefront.order', $payment['order']['id']))->assertSessionHas('status', 'Thanh toán VNPay thành công. Cảm ơn bạn!');
        expect(Payment::query()->sole()->status->value)->toBe('pending');

        $this->get('/p/vani-vnpay/return?'.http_build_query(vnpayParams($payment['id'], 330_000, responseCode: '24')))
            ->assertRedirect(route('storefront.order', $payment['order']['id']))->assertSessionHasErrors('business');
        $this->get('/p/vani-vnpay/return?'.http_build_query(vnpayParams($payment['id'], 330_000, tamper: ['vnp_ResponseCode' => '00', 'vnp_Amount' => '1'])))
            ->assertRedirect(route('storefront.home'))->assertSessionHasErrors('business');
    });

    it('checkout native chuyển khách sang VNPay; trang đơn hiện nút thanh toán lại khi chưa trả, "Đã nhận thanh toán" sau IPN', function () {
        $this->post('/gio-hang', ['variant_id' => $this->s->id]);
        $response = $this->post('/thanh-toan', [
            'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
            'shipping_address' => ['province_code' => '29', 'ward_code' => '70101065', 'street_line' => '12 Lê Lợi'],
            'shipping_method' => 'standard', 'payment_method' => 'vnpay', 'expected_total' => 330_000, 'idempotency_key' => 'native-vnpay-0001',
        ]);
        $payment = Payment::query()->sole();
        $order = Order::query()->withoutGlobalScopes()->sole();
        expect($response->headers->get('Location'))->toStartWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?');

        $this->get("/don-hang/{$order->public_id}")->assertOk()->assertSee('Thanh toán ngay');
        ($this->ipn)(vnpayParams($payment->public_id, 330_000))->assertJsonPath('RspCode', '00');
        $this->get("/don-hang/{$order->public_id}")->assertOk()->assertSee('Đã nhận thanh toán')->assertDontSee('Thanh toán ngay');
    });

    it('không khả dụng khi chưa cấu hình hoặc dưới 5.000 ₫', function () {
        $gateway = app(VnPayGateway::class);
        expect($gateway->isAvailable(new PaymentContext(Money::vnd(330_000))))->toBeTrue()
            ->and($gateway->isAvailable(new PaymentContext(Money::vnd(4_000))))->toBeFalse();

        config(['vani.vnpay.hash_secret' => '']);
        expect($gateway->isAvailable(new PaymentContext(Money::vnd(330_000))))->toBeFalse();
    });

    it('tra cứu querydr: đã trả / lỗi / chữ ký phản hồi sai → chờ', function () {
        $data = new PaymentData('01JVNPAYQUERY0000000000001', 'vnpay', 'VN2610-000001', Money::vnd(330_000), 'pending', 'VNP-01JVNPAYQUERY0000000000001-20261003100000', new DateTimeImmutable('2026-10-03T03:15:00Z'));
        $gateway = app(VnPayGateway::class);

        fakeVnPayApi(['query' => '00']);
        $status = $gateway->query($data);
        expect([$status->status, $status->gatewayTransactionId, $status->amount?->amount])->toBe(['paid', '14012345', 330_000]);
        Http::assertSent(fn (HttpRequest $request) => $request['vnp_Command'] === 'querydr' && $request['vnp_TransactionDate'] === '20261003100000');

        fakeVnPayApi(['query' => '02']);
        expect($gateway->query($data)->status)->toBe('failed');

        fakeVnPayApi(['tamper' => true]);
        expect($gateway->query($data)->status)->toBe('pending');
    });

    it('hoàn tiền: tra cứu giao dịch gốc rồi refund (toàn phần/một phần), idempotent theo khoá; VNPay từ chối → thất bại để hoàn tay', function () {
        $data = new PaymentData('01JVNPAYREFUND000000000001', 'vnpay', 'VN2610-000002', Money::vnd(330_000), 'paid', 'VNP-01JVNPAYREFUND000000000001-20261003100000', null);
        $gateway = app(VnPayGateway::class);

        fakeVnPayApi();
        $first = $gateway->refund($data, Money::vnd(100_000), 'refund-key-1');
        expect($first->successful)->toBeTrue()->and($first->gatewayReference)->toBe('VNP-RF-14099999');
        Http::assertSent(fn (HttpRequest $request) => $request['vnp_Command'] === 'refund' && $request['vnp_TransactionType'] === '03'
            && $request['vnp_Amount'] === 10_000_000 && $request['vnp_TransactionNo'] === '14012345');

        $second = $gateway->refund($data, Money::vnd(100_000), 'refund-key-1');
        expect($second->gatewayReference)->toBe($first->gatewayReference);
        Http::assertSentCount(2); // querydr + refund của lần đầu; lần hai không gọi VNPay

        fakeVnPayApi(['refund' => '94']);
        expect($gateway->refund($data, Money::vnd(330_000), 'refund-key-2')->successful)->toBeFalse();
    });
});
