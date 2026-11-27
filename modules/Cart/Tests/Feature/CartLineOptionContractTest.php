<?php

use Modules\Cart\Testing\CartLineOptionContract;
use Modules\Cart\Tests\Feature\Fixtures\EngravingOption;

/*
| P4/R26: implementation mẫu chạy bộ contract test của CartLineOption.
*/

CartLineOptionContract::define('fixture engraving', fn () => new EngravingOption, ['text' => 'VANI'], ['text' => str_repeat('A', 11)]);
