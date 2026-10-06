<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

/*
| Bất biến của bảng đối soát tồn kho (roadmap Phase 3):
|   R1 mỗi lần `vani:inventory:verify` tạo đúng một phiên (source internal_verify).
|   R2 discrepancies của phiên = số dòng; repaired = số dòng có resolution.
|   R3 dòng repaired luôn đi kèm movement `reconcile` mới (R30 — sửa qua ledger,
|      không UPDATE trực tiếp stock_levels).
| Cuối phiên sửa tự động, verify lại phải sạch (như trên production chạy hằng ngày).
*/

it('phiên đối soát phản ánh đúng số dòng; sau sửa reserved, verify lại sạch', function () {
    $brand = Brand::factory()->create();
    [$s] = P::variants(T::product($brand->id));
    $loc = I::location(['code' => 'WH-INV', 'priority' => 10]);
    I::stock($loc, $s->id, 6);
    app(CurrentContext::class)->set(ContextScope::system('reconciliation invariants'));

    $this->assertConsistent = function () {
        foreach (DB::table('inventory_reconciliations')->orderBy('id')->get() as $run) {
            $lines = DB::table('inventory_reconciliation_lines')->where('reconciliation_id', $run->id)->get();
            expect((int) $run->discrepancies)->toBe($lines->count(), 'R2 discrepancies của phiên #'.((int) $run->id))
                ->and((int) $run->repaired)->toBe($lines->whereNotNull('resolution')->count(), 'R2 repaired của phiên #'.((int) $run->id));
        }
    };

    $this->artisan('vani:inventory:verify')->assertSuccessful();

    // reserved = 9 nhưng không có hàng giữ active → reserved_mismatch (chỉ báo cáo).
    DB::table('stock_levels')->where('location_id', $loc->id)->where('variant_id', $s->id)->update(['reserved' => 9]);
    $this->artisan('vani:inventory:verify')->assertFailed();
    ($this->assertConsistent)();

    // --repair-reserved sửa qua ledger (R30) và đánh dấu dòng resolved.
    $this->artisan('vani:inventory:verify', ['--repair-reserved' => true])->assertSuccessful();
    ($this->assertConsistent)();

    $movement = DB::table('stock_movements')->latest('id')->first();
    expect([$movement->type, (int) $movement->reserved_delta, (int) $movement->reserved_after])->toBe(['reconcile', -9, 0]);

    // Lần chạy sau đó sạch và vẫn tạo phiên mới (lưu vết hằng ngày).
    $this->artisan('vani:inventory:verify')->assertSuccessful();
    ($this->assertConsistent)();

    expect(DB::table('inventory_reconciliations')->where('source', 'internal_verify')->count())->toBe(4)
        ->and((int) DB::table('inventory_reconciliations')->orderByDesc('id')->first()->discrepancies)->toBe(0);
});
