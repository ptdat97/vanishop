<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Trang giỏ: giỏ, mã giảm giá và báo giá checkout cùng đọc một giỏ trong một request — giỏ chỉ được dựng một lần.
*/

it('trang giỏ dựng giỏ một lần: dòng giỏ, giá, tồn mỗi thứ một truy vấn', function () {
    ['s' => $s, 'm' => $m] = C::store();
    $this->post('/gio-hang', ['variant_id' => $s->id, 'quantity' => 1]);
    $this->post('/gio-hang', ['variant_id' => $m->id, 'quantity' => 2]);
    app()->forgetScopedInstances();

    DB::enableQueryLog();
    $this->get('/gio-hang')->assertOk()->assertSee('300.000');
    $queries = array_column(DB::getQueryLog(), 'query');
    $count = fn (string $table): int => count(array_filter($queries, fn (string $sql): bool => (bool) preg_match('/^select .* from "'.$table.'"/', $sql)));

    expect($count('cart_lines'))->toBe(1)
        ->and($count('prices'))->toBe(1)
        ->and($count('stock_levels'))->toBe(1);
});
