<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Testing\BackorderPolicyContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Preorder\Infrastructure\IncomingBackorderPolicy;
use Plugin\Preorder\PreorderServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(PreorderServiceProvider::ID);
        app(PluginManager::class)->enable(PreorderServiceProvider::ID);
    });
    app()->register(PreorderServiceProvider::class);
});

BackorderPolicyContract::define('vani.preorder', fn () => app(IncomingBackorderPolicy::class), function (): array {
    ['s' => $s, 'm' => $m] = C::store(stock: 0);
    app(IncomingStock::class)->set($s->id, 10, '2026-12-01', 'staff');

    return [$s->id, $m->id];
});
