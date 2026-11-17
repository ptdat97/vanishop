<?php

use Modules\Cart\Testing\CartLineOptionContract;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Testing\ConfirmationChannelContract;
use Modules\Ordering\Testing\PackingSlipTemplateContract;
use Modules\Ordering\Tests\Fixtures\ContractOrders;
use Modules\Payment\Contracts\Data\RefundData;
use Modules\Payment\Testing\RefundPayoutContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\HelloWorld\HelloWorldServiceProvider;
use Plugin\HelloWorld\Infrastructure\GiftMessageOption;
use Plugin\HelloWorld\Infrastructure\HelloConfirmationChannel;
use Plugin\HelloWorld\Infrastructure\HelloPayout;
use Plugin\HelloWorld\Infrastructure\HelloSlipA6;

require_once __DIR__.'/../../../../../modules/Ordering/Tests/Fixtures/ContractOrders.php';

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install('vani.hello-world');
        app(PluginManager::class)->enable('vani.hello-world');
    });
    app()->register(HelloWorldServiceProvider::class);
});

CartLineOptionContract::define('vani.hello-world gift message', fn () => new GiftMessageOption, ['message' => 'Chúc mừng sinh nhật'], ['message' => str_repeat('a', GiftMessageOption::MAX + 1)]);
ConfirmationChannelContract::define('vani.hello-world', fn () => app(HelloConfirmationChannel::class), fn () => ContractOrders::data(ContractOrders::seed()));
PackingSlipTemplateContract::define('vani.hello-world A6', fn () => app(HelloSlipA6::class));
RefundPayoutContract::define('vani.hello-world', fn () => app(HelloPayout::class), fn () => new RefundData('01HREFUND000000000000ABC1', '01HPAY0000000000000000ABC1', 'CT000001', 120_000, 'VND', 'Đổi size', ['full_name' => 'Khách thử']));
