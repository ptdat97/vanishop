<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Promotion\Testing\PromotionActionContract;
use Modules\Promotion\Testing\PromotionRuleContract;
use Modules\Storefront\Testing\StorefrontBlockContract;
use Plugin\Bundles\BundlesServiceProvider;
use Plugin\Bundles\Infrastructure\BundleAction;
use Plugin\Bundles\Infrastructure\BundleBlock;
use Plugin\Bundles\Infrastructure\BundleRule;

/*
| Contract test của các extension point mà vani.bundles implement. Plugin được bật để hook trong hooks.php của plugin
| và view `vani-bundles::` có mặt.
*/

beforeEach(function () {
    Catalog::seed(function () {
        app(PluginManager::class)->install(BundlesServiceProvider::ID);
        app(PluginManager::class)->enable(BundlesServiceProvider::ID);
    });
    app()->register(BundlesServiceProvider::class);
    app(PluginActivation::class)->flush();
});

$items = ['items' => ['101' => 2, '102' => 1]];

PromotionRuleContract::define('vani.bundles bundle_complete', fn () => app(BundleRule::class), valid: $items, invalid: ['items' => ['101' => 1]]);
PromotionActionContract::define('vani.bundles bundle_percent', fn () => app(BundleAction::class), valid: [...$items, 'basis_points' => 1000], invalid: [...$items, 'basis_points' => 50]);
StorefrontBlockContract::define('vani.bundles bundle', fn () => app(BundleBlock::class), config: ['title' => 'Mua trọn bộ', 'bundle' => '999999']);
