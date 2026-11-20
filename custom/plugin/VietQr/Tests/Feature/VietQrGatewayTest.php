<?php

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Contracts\InvalidCallback;
use Modules\Payment\Contracts\QueriesStatus;
use Modules\Payment\Contracts\RefundsPayments;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Shared\Domain\Money\Money;
use Plugin\VietQr\Infrastructure\VietQrGateway;

$gateway = fn () => new VietQrGateway(
    account: [
        'bank_id' => '970436',
        'account_no' => '0123456789',
        'account_name' => 'CONG TY VANI',
    ],
    secret: 'test-secret',
);

$makeCallback = function (PaymentData $payment, string $status, ?int $amount = null, bool $tamper = false) use ($gateway): Request {
    $gw = $gateway();
    $amt = (string) ($amount ?? $payment->amount->amount);
    $fields = [
        'payment_id' => $payment->publicId,
        'transaction_id' => 'TXN-'.uniqid(),
        'status' => $status,
        'amount' => $amt,
    ];

    $sig = $gw->computeSignature($fields);
    if ($tamper) {
        $sig .= '-tampered';
    }

    return Request::create('/api/payments/vietqr/callback', 'POST', [...$fields, 'signature' => $sig]);
};

PaymentGatewayContract::define(
    'vani.vietqr',
    $gateway,
    validCallback: fn (PaymentData $p) => $makeCallback($p, 'paid'),
    tamperedCallback: fn (PaymentData $p) => $makeCallback($p, 'paid', tamper: true),
);

it('khoá ký callback trống: không khả dụng ở checkout và từ chối mọi callback (kể cả ký bằng khoá rỗng)', function () {
    $unsigned = new VietQrGateway(account: ['bank_id' => '970436', 'account_no' => '0123456789'], secret: '');
    $fields = ['payment_id' => '01JVIETQR0000000000000001', 'transaction_id' => 'TXN-1', 'status' => 'paid', 'amount' => '330000'];

    expect($unsigned->isAvailable(new PaymentContext(Money::vnd(330_000))))->toBeFalse()
        ->and(fn () => $unsigned->verifyCallback(Request::create('/api/payments/vietqr/callback', 'POST', [...$fields, 'signature' => $unsigned->computeSignature($fields)])))
        ->toThrow(InvalidCallback::class);
});

it('chuyển khoản không hoàn được qua cổng: không cài RefundsPayments/QueriesStatus (khoản hoàn chờ hoàn tay)', function () use ($gateway) {
    expect($gateway())->not->toBeInstanceOf(RefundsPayments::class)->not->toBeInstanceOf(QueriesStatus::class);
});
