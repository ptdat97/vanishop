<?php

use Modules\Storefront\Testing\StorefrontEnricherContract;
use Plugin\HelloWorld\Infrastructure\HelloEnricher;

StorefrontEnricherContract::define('vani.hello-world', fn () => new HelloEnricher, fn () => [['id' => 1, 'name' => 'Đầm'], ['id' => 2, 'name' => 'Áo']]);
