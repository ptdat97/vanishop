<?php

use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Testing\SitemapProviderContract;
use Plugin\Lookbook\Infrastructure\LookbookSitemap;
use Plugin\Lookbook\LookbookServiceProvider;
use Plugin\Lookbook\Persistence\Lookbook;

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(LookbookServiceProvider::ID);
        app(PluginManager::class)->enable(LookbookServiceProvider::ID);
    });
    app()->register(LookbookServiceProvider::class);
});

SitemapProviderContract::define('vani.lookbook', fn () => app(LookbookSitemap::class), function (): void {
    Lookbook::query()->create(['slug' => 'thu-2026', 'translations' => ['vi' => ['title' => 'Thu 2026']], 'active' => true]);
});
