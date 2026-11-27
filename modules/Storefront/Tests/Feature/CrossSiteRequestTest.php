<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Thêm vào giỏ (POST miễn kiểm token CSRF vì trang cache không có token): chặn request từ site khác theo Fetch Metadata /
| Origin — lớp thứ hai bên cạnh cookie SameSite=Lax. Beacon lượt xem cố ý nhận request khác nguồn (ProductViewsTest).
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->add = fn (array $headers) => $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1], $headers);
});

it('thêm vào giỏ từ chính trang cửa hàng được; client không gửi header trình duyệt cũng được', function (array $headers) {
    ($this->add)($headers)->assertRedirect();

    expect(DB::table('cart_lines')->count())->toBe(1);
})->with([
    'Sec-Fetch-Site: same-origin' => [['Sec-Fetch-Site' => 'same-origin']],
    'Origin trùng host (trình duyệt cũ)' => [fn (): array => ['Origin' => 'http://'.request()->getHost()]],
    'không header (không phải trình duyệt)' => [[]],
]);

it('thêm vào giỏ từ site khác (kể cả subdomain cùng tên miền) bị chặn, không tạo dòng giỏ', function (array $headers) {
    ($this->add)($headers)->assertForbidden();
    $this->postJson('/en/cart', ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertForbidden()->assertJsonPath('error.code', 'storefront.cross_site_request');

    expect(DB::table('cart_lines')->count())->toBe(0);
})->with([
    'Sec-Fetch-Site: cross-site' => [['Sec-Fetch-Site' => 'cross-site']],
    'Sec-Fetch-Site: same-site (subdomain khác)' => [['Sec-Fetch-Site' => 'same-site']],
    'Origin khác host' => [['Origin' => 'https://evil.example']],
]);
