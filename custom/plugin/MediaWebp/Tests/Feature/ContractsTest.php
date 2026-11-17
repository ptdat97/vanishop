<?php

use Modules\Catalog\Testing\ImageFormatContract;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\MediaWebp\Infrastructure\WebpFormat;
use Plugin\MediaWebp\MediaWebpServiceProvider;

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(MediaWebpServiceProvider::ID);
        app(PluginManager::class)->enable(MediaWebpServiceProvider::ID);
    });
    app()->register(MediaWebpServiceProvider::class);
});

ImageFormatContract::define('vani.media-webp', fn () => app(WebpFormat::class));
