<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s, 'location' => $this->location] = C::store();
    // Đơn giữ 1 hàng qua luồng thật → có hàng giữ active + biến động reserve.
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'verify-0001'])->assertCreated();
    $this->level = fn () => DB::table('stock_levels')->where('variant_id', $this->s->id)->where('location_id', $this->location->id);
});

it('tồn khớp hàng giữ và sổ biến động → không chênh lệch', function () {
    $this->artisan('vani:inventory:verify')->expectsOutputToContain('0 dòng sửa reserved')->assertSuccessful();
});

it('reserved lệch hàng giữ: báo lỗi; --repair-reserved sửa và ghi biến động reconcile', function () {
    ($this->level)()->update(['reserved' => 5]);

    $this->artisan('vani:inventory:verify')->expectsOutputToContain('reserved_mismatch')->assertFailed();
    expect((int) ($this->level)()->value('reserved'))->toBe(5);

    $this->artisan('vani:inventory:verify', ['--repair-reserved' => true])->assertSuccessful();
    $movement = DB::table('stock_movements')->latest('id')->first();
    expect((int) ($this->level)()->value('reserved'))->toBe(1)
        ->and([$movement->type, (int) $movement->reserved_delta, (int) $movement->reserved_after])->toBe(['reconcile', -4, 1]);

    $this->artisan('vani:inventory:verify')->assertSuccessful();
});

it('on_hand sửa ngoài sổ: chỉ báo cáo (cần kiểm kê), không tự sửa', function () {
    ($this->level)()->update(['on_hand' => 7]);

    $this->artisan('vani:inventory:verify', ['--repair-reserved' => true])->expectsOutputToContain('on_hand_off_ledger')->assertFailed();
    expect((int) ($this->level)()->value('on_hand'))->toBe(7);
});
