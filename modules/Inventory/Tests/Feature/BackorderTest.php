<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\BackorderPolicy;
use Modules\Inventory\Contracts\Backorders;
use Modules\Inventory\Contracts\Data\BackorderAllowance;
use Modules\Inventory\Contracts\Data\ReservationLine;
use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\StockUnavailable;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/InventoryTestHelpers.php';

/*
| ADR-047 (EP v3 X7): hạn mức đặt trước theo SKU ở kho bù tồn — không bộ đếm; hàng thật trước; hàng về lấp đơn cũ trước.
*/

final class FixedBackorderPolicy implements BackorderPolicy
{
    /** @var array<int, int> */
    public static array $limits = [];

    public function allowances(array $variantIds): array
    {
        return array_map(fn (int $limit): BackorderAllowance => new BackorderAllowance($limit, '2026-11-30'), array_intersect_key(self::$limits, array_flip($variantIds)));
    }
}

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    [$this->s, $this->m] = P::variants(T::product($this->brand->id));
    $this->hn = I::location(['code' => 'WH-HN', 'priority' => 10]);
    $this->erp = I::location(['code' => 'WH-ERP', 'priority' => 20, 'stock_authority' => 'erp']);
    app(CurrentContext::class)->set(new ContextScope(Actor::guest()));
    FixedBackorderPolicy::$limits = [$this->s->id => 3];
    app(Extensions::class)->tag([FixedBackorderPolicy::class], BackorderPolicy::TAG);

    $this->reserve = fn (string $key, int $quantity) => app(InventoryReservation::class)->reserve(new ReservationRequest($key, [new ReservationLine($this->s->id, $quantity)]));
    $this->ats = fn () => app(AvailabilityReader::class)->forVariants([$this->s->id, $this->m->id]);
    $this->level = fn ($location) => DB::table('stock_levels')->where('location_id', $location->id)->where('variant_id', $this->s->id)->first();
});

it('hết hàng thật vẫn bán được tới hạn mức ở kho bù tồn; vượt hạn mức thì báo hết; huỷ đơn trả suất', function () {
    expect(($this->ats)())->toBe([$this->s->id => 3, $this->m->id => 0]);

    ($this->reserve)('order:1', 2);
    expect(($this->level)($this->hn))->reserved->toBe(2)->on_hand->toBe(0)
        ->and(($this->ats)()[$this->s->id])->toBe(1)
        ->and(app(Backorders::class)->status([$this->s->id, $this->m->id]))->toBe([$this->s->id => ['limit' => 3, 'waiting' => 2, 'remaining' => 1, 'expected_on' => '2026-11-30']]);

    expect(fn () => ($this->reserve)('order:2', 2))->toThrow(StockUnavailable::class);

    app(InventoryReservation::class)->release('order:1', 'cancelled');
    expect(($this->ats)()[$this->s->id])->toBe(3);
});

it('hàng thật ở mọi kho trước, suất đặt trước sau; hàng về lấp cho đơn giữ trước', function () {
    I::stock($this->erp, $this->s->id, 1);
    I::stock($this->hn, $this->s->id, 1);
    expect(($this->ats)()[$this->s->id])->toBe(5);

    ($this->reserve)('order:1', 3); // ERP 1 + HN 1 thật + 1 chờ hàng
    ($this->reserve)('order:2', 1); // chờ hàng
    expect(($this->level)($this->erp)->reserved)->toBe(1)
        ->and(($this->level)($this->hn)->reserved)->toBe(3)
        ->and(app(Backorders::class)->waitingFor('order:1'))->toBe([$this->s->id => 1])
        ->and(app(Backorders::class)->waitingFor('order:2'))->toBe([$this->s->id => 1]);

    DB::table('stock_levels')->where('location_id', $this->hn->id)->where('variant_id', $this->s->id)->update(['on_hand' => 2]); // về 1
    expect(app(Backorders::class)->waitingFor('order:1'))->toBe([])
        ->and(app(Backorders::class)->waitingFor('order:2'))->toBe([$this->s->id => 1]);
});

it('không áp cho sản phẩm không theo dõi tồn, không có kho bù tồn hoặc policy lỗi', function () {
    T::seed(fn () => DB::table('products')->where('id', $this->s->product_id)->update(['track_inventory' => false]));
    expect(app(Backorders::class)->status([$this->s->id]))->toBe([]);
    T::seed(fn () => DB::table('products')->where('id', $this->s->product_id)->update(['track_inventory' => true]));

    T::seed(fn () => $this->hn->update(['ships_online_orders' => false]));
    expect(($this->ats)()[$this->s->id])->toBe(0)
        ->and(fn () => ($this->reserve)('order:1', 1))->toThrow(StockUnavailable::class);
});

it('IncomingStock ghi, đọc, xoá khi số lượng ≤ 0; ngày sai dạng bị từ chối', function () {
    $incoming = app(IncomingStock::class);
    $incoming->set($this->s->id, 20, '2026-11-30', 'staff');
    $createdAt = DB::table('incoming_stock')->value('created_at');
    $this->travel(1)->hours();
    $incoming->set($this->s->id, 12, null, 'system:erp');
    expect(DB::table('incoming_stock')->count())->toBe(1)->and(DB::table('incoming_stock')->value('created_at'))->toBe($createdAt);

    expect($incoming->forVariants([$this->s->id, $this->m->id]))->toHaveCount(1)
        ->and($incoming->forVariants([$this->s->id])[$this->s->id])->quantity->toBe(12)->expectedOn->toBeNull()->source->toBe('system:erp')
        ->and(fn () => $incoming->set($this->s->id, 5, '30/11/2026', 'staff'))->toThrow(InvalidArgumentException::class);

    $incoming->set($this->s->id, 0, null, 'staff');
    expect($incoming->forVariants([$this->s->id]))->toBe([]);
});
