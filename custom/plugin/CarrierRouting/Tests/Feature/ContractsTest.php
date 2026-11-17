<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Fulfillment\Contracts\Data\CarrierAssignmentContext;
use Modules\Fulfillment\Testing\CarrierAssignmentContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CarrierRouting\CarrierRoutingServiceProvider;
use Plugin\CarrierRouting\Infrastructure\RoutingRules;

require_once __DIR__.'/../../../../../modules/Catalog/Tests/Feature/CatalogTestHelpers.php';

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CarrierRoutingServiceProvider::ID);
        app(PluginManager::class)->enable(CarrierRoutingServiceProvider::ID);
    });
    app()->register(CarrierRoutingServiceProvider::class);
});

CarrierAssignmentContract::define('vani.carrier-routing', function () {
    T::seed(fn () => app(Settings::class)->set(CarrierRoutingServiceProvider::ID, 'rules', "tinh:01 = manual:express\ncod>=500000 = manual"));

    return app(RoutingRules::class);
}, fn () => [
    new CarrierAssignmentContext(1, 'CT000001', ['province_code' => '01'], 330_000, 330_000, 1, []),
    new CarrierAssignmentContext(2, 'CT000002', ['province_code' => '79'], 600_000, 600_000, 2, []),
    new CarrierAssignmentContext(3, 'CT000003', ['province_code' => '79'], 0, 200_000, 1, []),
]);
