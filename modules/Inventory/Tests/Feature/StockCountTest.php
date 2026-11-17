<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Persistence\Models\StockCount;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/InventoryTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A7: kiểm kê nhanh — số đếm lưu kèm tồn lúc đếm; áp dụng chỉ ghi phần chênh (hàng bán trong lúc đếm giữ nguyên),
| một lần mỗi phiên; SKU chưa đếm không đổi.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $this->location = I::location(['code' => 'KHO-CHINH']);
    [$this->s, $this->m, $this->l] = P::variants(T::product(null, ['style_code' => 'DAM01']), ['S', 'M', 'L']);
    I::stock($this->location, $this->s->id, 10);
    I::stock($this->location, $this->m->id, 4);
    I::stock($this->location, $this->l->id, 7);
    $this->onHand = fn ($variant): int => (int) DB::table('stock_levels')->where('location_id', $this->location->id)->where('variant_id', $variant->id)->value('on_hand');
    $this->post('/admin/inventory/counts', ['location_id' => $this->location->id, 'note' => 'Kệ A'])->assertRedirect();
    $this->count = StockCount::query()->sole();
    $this->sell = fn ($variant, int $quantity) => app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StockAdjustmentService::class)->adjust($this->location, $variant->id, -$quantity, 'bán'));
});

it('quét cộng dồn, sửa số đếm; bán trong lúc đếm → áp dụng chỉ ghi phần chênh; SKU chưa đếm không đổi', function () {
    expect($this->count->number)->toMatch('/^KK\d{4}-0001$/');
    $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => "{$this->s->sku}\n{$this->s->sku}\n{$this->s->sku} 6"])->assertOk()->assertJson(['recorded' => 1]);
    $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => $this->m->sku])->assertOk();
    $this->put("/admin/inventory/counts/{$this->count->id}/lines/{$this->m->id}", ['counted' => 5])->assertRedirect();

    // Đếm xong S (8 cái, hệ thống 10 → thiếu 2), sau đó bán 3 S và 1 M.
    ($this->sell)($this->s, 3);
    ($this->sell)($this->m, 1);

    $this->get("/admin/inventory/counts/{$this->count->id}")->assertInertia(fn (Assert $page) => $page
        ->component('Inventory::Counts/Show')
        ->where('lines', fn ($lines): bool => collect($lines)->keyBy('sku')->map(fn (array $line): array => [$line['counted'], $line['on_hand_at_count'], $line['on_hand'], $line['delta'], $line['new_on_hand']])->all() === [
            $this->m->sku => [5, 4, 3, 1, 4],
            $this->s->sku => [8, 10, 7, -2, 5],
        ]));

    $this->post("/admin/inventory/counts/{$this->count->id}/apply")->assertSessionHas('success', "Đã áp dụng kiểm kê {$this->count->number}: 2 SKU đổi tồn, 0 SKU khớp.");
    expect(($this->onHand)($this->s))->toBe(5)
        ->and(($this->onHand)($this->m))->toBe(4)
        ->and(($this->onHand)($this->l))->toBe(7)
        ->and(DB::table('stock_movements')->where('reference', $this->count->number)->orderBy('on_hand_delta')->pluck('on_hand_delta')->all())->toEqual([-2, 1]);

    $this->post("/admin/inventory/counts/{$this->count->id}/apply")->assertSessionHasErrors('count');
    $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => $this->s->sku])->assertStatus(422);
    expect(($this->onHand)($this->s))->toBe(5);
});

it('chênh âm lớn hơn tồn hiện tại (đã bán hết phần còn lại) → ghi tới 0, không lỗi', function () {
    $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => "{$this->m->sku} 1"]);
    ($this->sell)($this->m, 4);

    $this->post("/admin/inventory/counts/{$this->count->id}/apply")->assertSessionHasNoErrors();
    expect(($this->onHand)($this->m))->toBe(0);
});

it('bỏ SKU khỏi phiên; huỷ phiên không ghi tồn; SKU lạ được báo', function () {
    $result = $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => "{$this->s->sku} 2\nKHONG-CO"])->assertOk();
    expect($result->json('errors'))->toHaveCount(1);
    $this->put("/admin/inventory/counts/{$this->count->id}/lines/{$this->s->id}", ['counted' => null]);
    expect($this->count->lines()->count())->toBe(0);

    $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => "{$this->s->sku} 2"]);
    $this->post("/admin/inventory/counts/{$this->count->id}/cancel")->assertSessionHasNoErrors();
    $this->post("/admin/inventory/counts/{$this->count->id}/apply")->assertSessionHasErrors('count');
    expect(($this->onHand)($this->s))->toBe(10);
});

it('chỉ xem tồn: xem được phiên, không mở/đếm/áp dụng được', function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $this->get('/admin/inventory/counts')->assertOk();
    $this->get("/admin/inventory/counts/{$this->count->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->where('canManage', false));
    $this->post('/admin/inventory/counts', ['location_id' => $this->location->id])->assertForbidden();
    $this->postJson("/admin/inventory/counts/{$this->count->id}/lines", ['text' => $this->s->sku])->assertForbidden();
    $this->post("/admin/inventory/counts/{$this->count->id}/apply")->assertForbidden();
});
