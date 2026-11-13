<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/CheckoutTestHelpers.php';

/*
| ADR-045 (F3): dòng giỏ/đơn mang tổ hợp tuỳ chọn của SKU `variant_options` = [{type, name, code, value}] theo thứ tự trục
| (không còn color_name/size_code cố định); đơn chụp lại lúc đặt, đổi tên giá trị sau đó không đổi đơn.
*/

it('giỏ và đơn mang tổ hợp tuỳ chọn của SKU; đơn giữ snapshot khi giá trị đổi tên', function () {
    ['s' => $small] = C::store();
    $api = '/api/storefront/v1';
    $created = $this->postJson("{$api}/carts")->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');

    $line = $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $small->id, 'quantity' => 1], $headers)->assertOk()->json('data.lines.0.product');
    $size = $small->valueOf('size');
    expect(array_column($line['variant_options'], 'type'))->toBe(['color', 'size'])
        ->and($line['variant_options'][1])->toMatchArray(['name' => 'Size', 'code' => $size->code])
        ->and($line['variant_label'])->toEndWith(' / '.$size->label());

    // 300.000 + phí giao 30.000 (chưa đủ mức miễn phí).
    $placed = $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();
    $order = Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    expect($order->lines()->sole()->variant_options)->toBe($line['variant_options']);

    $size->syncTranslations(['vi' => ['name' => 'Nhỏ']]);
    $this->getJson("{$api}/orders/{$order->public_id}", ['X-Vani-Order-Token' => $placed->json('data.access_token')])->assertOk()
        ->assertJsonPath('data.lines.0.variant_label', $line['variant_label'])
        ->assertJsonPath('data.lines.0.variant_options.1.code', $size->code);
});
