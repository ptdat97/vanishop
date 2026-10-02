<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Payment\Contracts\Data\PaymentContext;
use Modules\Payment\Testing\PaymentGatewayContract;
use Modules\Shared\Domain\Money\Money;
use Modules\Tenancy\Contracts\Settings;
use Plugin\BankTransfer\Infrastructure\ManualBankTransferGateway;

PaymentGatewayContract::define('vani.bank-transfer', function () {
    config(['vani.bank-transfer.account' => ['bank' => 'Vietcombank', 'account_number' => '0123456789', 'account_name' => 'CONG TY VANI']]);

    return app(ManualBankTransferGateway::class);
});

it('thiếu số tài khoản → ẩn; tài khoản đặt trong Admin ghi đè .env', function () {
    config(['vani.bank-transfer.account' => ['bank' => '', 'account_number' => '', 'account_name' => '']]);
    $gateway = app(ManualBankTransferGateway::class);
    $context = new PaymentContext(Money::vnd(330_000));

    expect($gateway->isAvailable($context))->toBeFalse();

    T::seed(function () {
        app(Settings::class)->set('vani.bank-transfer', 'bank', 'ACB');
        app(Settings::class)->set('vani.bank-transfer', 'account_number', '999888');
        app(Settings::class)->set('vani.bank-transfer', 'account_name', 'VANI');
    });

    expect($gateway->isAvailable($context))->toBeTrue()
        ->and($gateway->capabilities()->manualConfirmation)->toBeTrue();
});
