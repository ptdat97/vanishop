<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Support\SimpleXlsx;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Stabilization A8: xuất đơn ra Excel theo đúng bộ lọc của danh sách — mỗi đơn một dòng hoặc mỗi dòng SKU một dòng; ghi audit.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 20);
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));
    $this->place = function (int $quantity): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => $quantity], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => $quantity * 300_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->first = ($this->place)(2);
    $this->second = ($this->place)(3);
    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    $this->rows = fn (string $query): array => SimpleXlsx::read($this->get('/admin/orders/orders/export?'.$query)->assertOk()->assertDownload()->getFile()->getPathname());
});

it('mỗi đơn một dòng, mới nhất trước: số đơn, trạng thái, khách, SĐT, địa chỉ, số tiền; ghi audit', function () {
    $rows = ($this->rows)('mode=orders');

    expect($rows[0])->toContain('Số đơn', 'Số điện thoại', 'Tổng')
        ->and(array_column(array_slice($rows, 1), 0))->toBe([$this->second->number, $this->first->number])
        ->and($rows[1][2])->toBe('Chờ xác nhận')
        ->and($rows[1][7])->toBe('+84912345678')
        ->and((int) $rows[1][15])->toBe(900_000)
        ->and(json_decode((string) DB::table('audit_logs')->where('action', 'orders.exported')->value('changes'), true))->toMatchArray(['mode' => 'orders', 'rows' => 2]);
});

it('theo dòng SKU và theo bộ lọc của danh sách (số đơn)', function () {
    $lines = ($this->rows)('mode=lines&q='.$this->first->number);

    expect($lines)->toHaveCount(2)
        ->and($lines[1][0])->toBe($this->first->number)
        ->and($lines[1][3])->toBe($this->s->sku)
        ->and((int) $lines[1][6])->toBe(2);
});

it('không có quyền xem đơn → 403', function () {
    $this->actingAs(T::staff(['admin.access']), 'staff');
    $this->get('/admin/orders/orders/export')->assertForbidden();
});
