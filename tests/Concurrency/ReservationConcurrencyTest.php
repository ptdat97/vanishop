<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Contracts\Data\ReservationLine;
use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\StockUnavailable;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/*
| Rule R14: giữ hàng phải atomic. Chạy thật nhiều tiến trình PHP song song trên MySQL (không dùng transaction
| của test để các tiến trình con thấy dữ liệu). Chạy: DB_CONNECTION=mysql DB_DATABASE=vanishop_testing vendor/bin/pest --group=concurrency
*/

require_once __DIR__.'/../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

uses(DatabaseTruncation::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }

    $this->brand = Brand::factory()->create();
    [$this->a, $this->b] = P::variants(T::product($this->brand->id));
    $this->location = I::location(['code' => 'WH-HN']);
});

/**
 * Tạo các tác vụ giữ hàng chạy ở tiến trình riêng. Mỗi tác vụ trả "ok" hoặc "unavailable".
 *
 * @param  list<list<array{0: int, 1: int}>>  $orders  mỗi đơn là danh sách [variant_id, qty]
 * @return list<Closure>
 */
function reservationTasks(array $orders): array
{
    $tasks = [];

    foreach ($orders as $index => $lines) {
        $tasks[] = function () use ($lines, $index): string {
            app(CurrentContext::class)->set(new ContextScope(Actor::guest()));

            $reservationLines = [];
            foreach ($lines as $line) {
                $reservationLines[] = new ReservationLine($line[0], $line[1]);
            }

            try {
                app(InventoryReservation::class)->reserve(new ReservationRequest("order:{$index}", $reservationLines));

                return 'ok';
            } catch (StockUnavailable) {
                return 'unavailable';
            }
        };
    }

    return $tasks;
}

it('không bán vượt tồn khi 12 tiến trình cùng mua SKU chỉ còn 5', function () {
    I::stock($this->location, $this->a->id, 5);

    $results = Concurrency::driver('process')->run(
        reservationTasks(array_fill(0, 12, [[$this->a->id, 1]])),
    );

    $counts = array_count_values($results);
    ksort($counts);

    expect($counts)->toBe(['ok' => 5, 'unavailable' => 7])
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->a->id)->value('reserved'))->toBe(5)
        ->and((int) DB::table('stock_reservations')->where('status', 'active')->sum('quantity'))->toBe(5)
        ->and(DB::table('stock_movements')->where('type', 'reserve')->count())->toBe(5);
});

it('không deadlock khi các đơn giữ nhiều SKU theo thứ tự ngược nhau', function () {
    I::stock($this->location, $this->a->id, 50);
    I::stock($this->location, $this->b->id, 50);

    $orders = [];
    for ($i = 0; $i < 10; $i++) {
        $orders[] = $i % 2 === 0 ? [[$this->a->id, 1], [$this->b->id, 1]] : [[$this->b->id, 1], [$this->a->id, 1]];
    }

    $results = Concurrency::driver('process')->run(reservationTasks($orders));

    expect(array_count_values($results))->toBe(['ok' => 10])
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->a->id)->value('reserved'))->toBe(10)
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->b->id)->value('reserved'))->toBe(10);
});
