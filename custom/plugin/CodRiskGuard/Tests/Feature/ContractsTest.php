<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Testing\OrderRiskSignalContract;
use Modules\Ordering\Tests\Fixtures\ContractOrders;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CodRiskGuard\CodRiskGuardServiceProvider;
use Plugin\CodRiskGuard\Infrastructure\RiskRules;

require_once __DIR__.'/../../../../../modules/Catalog/Tests/Feature/CatalogTestHelpers.php';
require_once __DIR__.'/../../../../../modules/Ordering/Tests/Fixtures/ContractOrders.php';

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CodRiskGuardServiceProvider::ID);
        app(PluginManager::class)->enable(CodRiskGuardServiceProvider::ID);
    });
    app()->register(CodRiskGuardServiceProvider::class);
});

OrderRiskSignalContract::define('vani.cod-risk-guard', function () {
    T::seed(fn () => app(Settings::class)->set(CodRiskGuardServiceProvider::ID, 'blacklist', '0987000000'));

    return app(RiskRules::class);
}, fn () => [ContractOrders::seed('0987000000'), ContractOrders::seed('0933000111')]);
