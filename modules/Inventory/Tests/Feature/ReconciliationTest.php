<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Persistence\Models\InventoryReconciliation;
use Modules\Inventory\Persistence\Models\InventoryReconciliationLine;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/InventoryTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->style = T::product($this->brand->id, ['style_code' => 'LM-REC']);
    [$this->s, $this->m] = P::variants($this->style);
    $this->loc = I::location(['code' => 'WH-REC', 'priority' => 10]);
    $this->level = fn () => DB::table('stock_levels')->where('location_id', $this->loc->id)->where('variant_id', $this->s->id);
    $this->runs = fn () => DB::table('inventory_reconciliations')->orderByDesc('id')->get();
    $this->lines = fn (int $runId) => DB::table('inventory_reconciliation_lines')->where('reconciliation_id', $runId)->orderBy('id')->get();
});

it('vani:inventory:verify lưu phiên đối soát nội bộ; khỏe mạnh thì không có chênh lệch', function () {
    I::stock($this->loc, $this->s->id, 5);

    $this->artisan('vani:inventory:verify')->assertSuccessful();

    expect(DB::table('inventory_reconciliations')->count())->toBe(1);
    $run = ($this->runs)()->first();
    expect($run->source)->toBe(InventoryReconciliation::SOURCE_INTERNAL_VERIFY)
        ->and((int) $run->checked)->toBe(1)
        ->and((int) $run->discrepancies)->toBe(0)
        ->and((int) $run->repaired)->toBe(0)
        ->and($run->finished_at)->not->toBeNull()
        ->and(DB::table('inventory_reconciliation_lines')->count())->toBe(0);
});

it('reserved lệch hàng giữ: ghi line mở; --repair-reserved ghi line đã sửa + movement reconcile', function () {
    I::stock($this->loc, $this->s->id, 5);
    ($this->level)()->update(['reserved' => 5]);

    $this->artisan('vani:inventory:verify')->expectsOutputToContain('reserved_mismatch')->assertFailed();
    $run = ($this->runs)()->first();
    $line = ($this->lines)((int) $run->id)->first();
    expect((int) $run->discrepancies)->toBe(1)
        ->and((int) $run->repaired)->toBe(0)
        ->and($line->classification)->toBe('reserved_mismatch')
        ->and((int) $line->expected)->toBe(0)
        ->and((int) $line->actual)->toBe(5)
        ->and((int) $line->difference)->toBe(-5)
        ->and($line->resolution)->toBeNull()
        ->and($line->resolved_at)->toBeNull();

    $this->artisan('vani:inventory:verify', ['--repair-reserved' => true])->assertSuccessful();
    $run2 = ($this->runs)()->first();
    $line2 = ($this->lines)((int) $run2->id)->first();
    expect((int) $run2->discrepancies)->toBe(1)
        ->and((int) $run2->repaired)->toBe(1)
        ->and($line2->resolution)->toBe(InventoryReconciliationLine::RESOLUTION_REPAIRED)
        ->and($line2->resolved_at)->not->toBeNull()
        ->and((int) ($this->level)()->value('reserved'))->toBe(0);

    $movement = DB::table('stock_movements')->latest('id')->first();
    expect([$movement->type, (int) $movement->reserved_delta])->toBe(['reconcile', -5]);
});

it('tồn sửa ngoài sổ: ghi line mở (cần kiểm kê), không tự sửa', function () {
    app(StockAdjustmentService::class)->adjust($this->loc, $this->s->id, 5, 'test');
    ($this->level)()->update(['on_hand' => 7]);

    $this->artisan('vani:inventory:verify')->expectsOutputToContain('on_hand_off_ledger')->assertFailed();
    $run = ($this->runs)()->first();
    $line = ($this->lines)((int) $run->id)->first();
    expect($line->classification)->toBe('on_hand_off_ledger')
        ->and((int) $line->expected)->toBe(5)
        ->and((int) $line->actual)->toBe(7)
        ->and((int) $line->difference)->toBe(-2)
        ->and($line->resolution)->toBeNull()
        ->and((int) ($this->level)()->value('on_hand'))->toBe(7);
});

it('màn hình đối soát cần quyền xem tồn kho và hiển thị phiên đã chạy', function () {
    I::stock($this->loc, $this->s->id, 5);
    $this->artisan('vani:inventory:verify')->assertSuccessful();
    $run = ($this->runs)()->first();

    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $this->get('/admin/inventory/reconciliations')->assertOk();
    $this->get("/admin/inventory/reconciliations/{$run->id}")->assertOk();

    $this->actingAs(T::staff(['admin.access']), 'staff');
    $this->get('/admin/inventory/reconciliations')->assertForbidden();
});
