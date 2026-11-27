<?php

use Modules\Ordering\Testing\ConfirmationChannelContract;
use Modules\Ordering\Testing\PackingSlipTemplateContract;
use Modules\Ordering\Tests\Fixtures\ContractOrders;
use Modules\Ordering\Tests\Fixtures\CoreViewSlipTemplate;
use Modules\Ordering\Tests\Fixtures\LogConfirmationChannel;

require_once __DIR__.'/../Fixtures/ContractOrders.php';
require_once __DIR__.'/../Fixtures/ContractExtensions.php';

/*
| P4/R26: implementation mẫu chạy bộ contract test của ConfirmationChannel và PackingSlipTemplate.
*/

ConfirmationChannelContract::define('fixture log', fn () => new LogConfirmationChannel, fn () => ContractOrders::data(ContractOrders::seed()));
PackingSlipTemplateContract::define('fixture A5', fn () => new CoreViewSlipTemplate);
