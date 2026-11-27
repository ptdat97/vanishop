<?php

use Modules\Storefront\Testing\StorefrontEnricherContract;
use Modules\Storefront\Tests\Feature\Fixtures\NameLengthEnricher;

require_once __DIR__.'/Fixtures/Enrichers.php';

/*
| P4/R26: implementation mẫu chạy bộ contract test của StorefrontEnricher (plugin thật chạy bộ này trong Tests của mình).
*/

StorefrontEnricherContract::define('fixture name length', fn () => new NameLengthEnricher, fn () => [['id' => 1, 'name' => 'Đầm'], ['id' => 2, 'name' => 'Áo sơ mi']]);
