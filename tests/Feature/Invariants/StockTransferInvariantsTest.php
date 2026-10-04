<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Application\StockTransferService;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

/*
| Bất biến tồn kho khi có chuyển kho (roadmap Phase 1):
|   I1 reserved của mỗi stock_level = tổng hàng giữ đang active; on_hand, reserved ≥ 0.
|   I2 thay đổi on_hand/reserved kể từ mốc = tổng stock_movements (sổ biến động đầy đủ).
| Cuối mỗi bước vòng đời chạy `vani:inventory:verify` như trên production.
*/

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    [$this->s, $this->m] = P::variants(T::product($this->brand->id));
    $this->hn = I::location(['code' => 'WH-HN', 'priority' => 10]);
    $this->hcm = I::location(['code' => 'WH-HCM', 'priority' => 5]);
    app(CurrentContext::class)->set(ContextScope::system('transfer invariants'));
    $this->service = app(StockTransferService::class);

    $this->baseline = null;
    $this->snapshot = function () {
        $this->baseline = DB::table('stock_levels')->get(['location_id', 'variant_id', 'on_hand', 'reserved'])
            ->keyBy(fn ($row) => "{$row->location_id}:{$row->variant_id}")->all();
        $this->movementsFrom = (int) DB::table('stock_movements')->max('id');
    };
    $this->assertInvariants = function () {
        foreach (DB::table('stock_levels')->get() as $level) {
            $key = "{$level->location_id}:{$level->variant_id}";
            $active = (int) DB::table('stock_reservations')->where(['location_id' => $level->location_id, 'variant_id' => $level->variant_id, 'status' => 'active'])->sum('quantity');
            $moves = DB::table('stock_movements')->where('id', '>', $this->movementsFrom)->where(['location_id' => $level->location_id, 'variant_id' => $level->variant_id])
                ->selectRaw('coalesce(sum(on_hand_delta), 0) as on_hand, coalesce(sum(reserved_delta), 0) as reserved')->first();
            $base = $this->baseline[$key] ?? (object) ['on_hand' => 0, 'reserved' => 0];

            expect((int) $level->reserved)->toBe($active, "I1 reserved [{$key}]")
                ->and((int) $level->on_hand)->toBeGreaterThanOrEqual(0)
                ->and((int) $level->reserved)->toBeGreaterThanOrEqual(0)
                ->and((int) $level->on_hand - (int) $base->on_hand)->toBe((int) $moves->on_hand, "I2 on_hand [{$key}]")
                ->and((int) $level->reserved - (int) $base->reserved)->toBe((int) $moves->reserved, "I2 reserved [{$key}]");
        }

        $this->artisan('vani:inventory:verify')->assertSuccessful();
    };
});

it('vòng đời chuyển kho đầy đủ (pending → shipped → received) giữ bất biến tồn', function () {
    I::stock($this->hn, $this->s->id, 8);
    I::stock($this->hn, $this->m->id, 5);
    ($this->snapshot)();

    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 3, $this->m->id => 2]);
    ($this->assertInvariants)();

    $this->service->ship($transfer->fresh());
    ($this->assertInvariants)();
    expect((int) DB::table('stock_levels')->where('location_id', $this->hn->id)->where('variant_id', $this->s->id)->value('on_hand'))->toBe(5);

    $this->service->receive($transfer->fresh());
    ($this->assertInvariants)();
    expect((int) DB::table('stock_levels')->where('location_id', $this->hcm->id)->where('variant_id', $this->s->id)->value('on_hand'))->toBe(3);
});

it('huỷ sau khi gửi và nhận thiếu giữ bất biến tồn', function () {
    I::stock($this->hn, $this->s->id, 10);
    ($this->snapshot)();

    $cancelled = $this->service->create($this->hn, $this->hcm, [$this->s->id => 5]);
    $this->service->ship($cancelled->fresh());
    ($this->assertInvariants)();
    $this->service->cancel($cancelled->fresh(), 'hết xe');
    ($this->assertInvariants)();
    expect((int) DB::table('stock_levels')->where('location_id', $this->hn->id)->where('variant_id', $this->s->id)->value('on_hand'))->toBe(10);

    $partial = $this->service->create($this->hn, $this->hcm, [$this->s->id => 4]);
    $this->service->ship($partial->fresh());
    $this->service->receive($partial->fresh(), [$this->s->id => 1]);
    ($this->assertInvariants)();
    expect((int) DB::table('stock_levels')->where('location_id', $this->hcm->id)->where('variant_id', $this->s->id)->value('on_hand'))->toBe(1);
});
