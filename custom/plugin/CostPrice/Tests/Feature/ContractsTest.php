<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Testing\VariantUsageContract;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Pricing\Testing\BulkPriceModeContract;
use Modules\Pricing\Testing\PriceRoundingContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\CostPrice\CostPriceServiceProvider;
use Plugin\CostPrice\Infrastructure\CostMarkupMode;
use Plugin\CostPrice\Infrastructure\CostPriceUsage;
use Plugin\CostPrice\Infrastructure\NineThousandRounding;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CostPriceServiceProvider::ID);
        app(PluginManager::class)->enable(CostPriceServiceProvider::ID);
    });
    app()->register(CostPriceServiceProvider::class);
});

PriceRoundingContract::define('vani.cost-price nine_thousand', fn () => app(NineThousandRounding::class));
BulkPriceModeContract::define('vani.cost-price cost_markup', fn () => app(CostMarkupMode::class), function (): array {
    ['s' => $s, 'm' => $m] = C::store();
    DB::table('plg_cost_prices')->insert(['variant_id' => $s->id, 'amount' => 150_000, 'created_at' => now(), 'updated_at' => now()]);

    return [[$s->id, $m->id], 40.0, [$s->id => 300_000, $m->id => 200_000]];
});
VariantUsageContract::define('vani.cost-price', fn () => app(CostPriceUsage::class), function (): array {
    ['s' => $s, 'm' => $m] = C::store();

    return [$s->id, $m->id];
});
