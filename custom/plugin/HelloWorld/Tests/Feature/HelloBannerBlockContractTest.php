<?php

use Modules\Storefront\Testing\StorefrontBlockContract;
use Plugin\HelloWorld\Infrastructure\HelloBannerBlock;

StorefrontBlockContract::define('vani.hello-world hello_banner', function () {
    view()->addNamespace('vani-hello-world', base_path('custom/plugin/HelloWorld/Resources/views'));

    return new HelloBannerBlock;
}, ['message' => 'Xin chào']);
