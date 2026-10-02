<?php

use Illuminate\Http\Request;
use Modules\Payment\Contracts\Data\PaymentData;
use Modules\Payment\Testing\PaymentGatewayContract;
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
