<?php

use Modules\Catalog\Testing\VariantUsageContract;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Inventory\Testing\StockPageLinkContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Warehouse\Infrastructure\LocationsLink;
use Plugin\Warehouse\Infrastructure\TransferredVariants;
use Plugin\Warehouse\Infrastructure\TransfersLink;
use Plugin\Warehouse\WarehouseServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(WarehouseServiceProvider::ID);
        app(PluginManager::class)->enable(WarehouseServiceProvider::ID);
    });
    app()->register(WarehouseServiceProvider::class);
});

StockPageLinkContract::define('vani.warehouse transfers', fn () => app(TransfersLink::class));
StockPageLinkContract::define('vani.warehouse locations', fn () => app(LocationsLink::class));
VariantUsageContract::define('vani.warehouse transferred', fn () => app(TransferredVariants::class), function (): array {
    ['s' => $s, 'm' => $m] = C::store();

    return [$s->id, $m->id];
});
