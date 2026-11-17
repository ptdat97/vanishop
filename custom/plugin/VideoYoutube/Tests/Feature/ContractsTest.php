<?php

use Modules\Catalog\Testing\VideoDriverContract;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\VideoYoutube\Infrastructure\YoutubeDriver;
use Plugin\VideoYoutube\VideoYoutubeServiceProvider;

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(VideoYoutubeServiceProvider::ID);
        app(PluginManager::class)->enable(VideoYoutubeServiceProvider::ID);
    });
    app()->register(VideoYoutubeServiceProvider::class);
});

VideoDriverContract::define('vani.video-youtube', fn () => app(YoutubeDriver::class), 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
