<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Application\ChannelAvailability;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\Data\ReservationLine;
use Modules\Inventory\Contracts\Data\ReservationRequest;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Inventory\Contracts\StockUnavailable;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Inventory\Persistence\Models\StockMovement;
use Modules\Inventory\Persistence\Models\StockReservation;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/InventoryTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->other = Brand::factory()->create();
    $this->channel = Channel::factory()->forBrand($this->brand, 'vani.test', '/lumiere')->create();
    [$this->s, $this->m] = P::variants(T::product($this->brand->id));
    $this->hn = I::location($this->brand, [$this->channel->id], ['code' => 'WH-HN', 'priority' => 10]);
    $this->hcm = I::location($this->brand, [$this->channel->id], ['code' => 'WH-HCM', 'priority' => 5]);
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), $this->channel->id, [$this->brand->id]));

    $this->reservations = app(InventoryReservation::class);
    $this->request = fn (string $key, array $lines, ?int $ttl = null) => new ReservationRequest($key, $this->channel->id,
        array_map(fn (array $line) => new ReservationLine($line[0], $line[1]), $lines), $ttl);
    $this->level = fn ($location, $variant) => DB::table('stock_levels')->where('location_id', $location->id)->where('variant_id', $variant->id)->first();
});

it('giữ hàng ở location ưu tiên cao nhất, ghi sổ biến động', function () {
    I::stock($this->hn, $this->s->id, 5);
    I::stock($this->hcm, $this->s->id, 5);

    $lines = $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 2]]));

    expect($lines)->toHaveCount(1)
        ->and($lines[0]->locationId)->toBe($this->hn->id)
        ->and(($this->level)($this->hn, $this->s)->reserved)->toBe(2)
        ->and(StockMovement::query()->where('reference', 'order:1')->where('type', 'reserve')->value('reserved_after'))->toBe(2);
});

it('tách sang location kế tiếp khi location đầu không đủ', function () {
    I::stock($this->hn, $this->s->id, 2);
    I::stock($this->hcm, $this->s->id, 5);

    $lines = $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 4]]));

    expect(collect($lines)->pluck('quantity', 'locationId')->all())->toBe([$this->hn->id => 2, $this->hcm->id => 2]);
});

it('tồn an toàn không được bán; thiếu hàng thì không giữ dòng nào (rollback toàn bộ)', function () {
    I::stock($this->hn, $this->s->id, 10);
    I::stock($this->hn, $this->m->id, 3, safetyStock: 2);

    expect(fn () => $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 1], [$this->m->id, 2]])))
        ->toThrow(StockUnavailable::class);

    expect(DB::table('stock_levels')->sum('reserved'))->toBe(0)
        ->and(StockReservation::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0);
});

it('không dùng location không phục vụ kênh, đã tắt, hoặc không bán brand', function () {
    $offline = I::location($this->brand, [], ['code' => 'WH-OFF']);
    $disabled = I::location($this->brand, [$this->channel->id], ['code' => 'WH-DIS', 'status' => 'inactive']);
    $noOnline = I::location($this->brand, [$this->channel->id], ['code' => 'ST-01', 'ships_online_orders' => false]);
    foreach ([$offline, $disabled, $noOnline] as $location) {
        I::stock($location, $this->s->id, 50);
    }

    expect(fn () => $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 1]])))->toThrow(StockUnavailable::class);
    expect(app(AvailabilityReader::class)->forChannel([$this->s->id], $this->channel->id))->toBe([$this->s->id => 0]);
});

it('biến thể ngừng bán không giữ được', function () {
    I::stock($this->hn, $this->s->id, 5);
    T::seed(fn () => $this->s->update(['status' => 'inactive']));

    $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 1]]));
})->throws(StockUnavailable::class);

it('idempotent theo key: gọi lại không giữ thêm', function () {
    I::stock($this->hn, $this->s->id, 5);

    $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 2]]));
    $again = $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 2]]));

    expect($again)->toHaveCount(1)->and(($this->level)($this->hn, $this->s)->reserved)->toBe(2);
});

it('release trả hàng về; release/commit lần hai không có tác dụng', function () {
    I::stock($this->hn, $this->s->id, 5);
    $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 2]]));

    $this->reservations->release('order:1', 'cancelled');
    $this->reservations->release('order:1', 'cancelled');
    $this->reservations->commit('order:1');

    expect(($this->level)($this->hn, $this->s))->reserved->toBe(0)->on_hand->toBe(5)
        ->and(StockReservation::query()->value('status')->value)->toBe('released');
});

it('commit trừ on_hand nếu VaniShop quản lý tồn; nguồn ngoài thì chỉ bỏ giữ', function () {
    $erp = I::location($this->brand, [$this->channel->id], ['code' => 'WH-ERP', 'priority' => 99, 'stock_authority' => 'erp-main']);
    I::stock($erp, $this->s->id, 1);
    I::stock($this->hn, $this->s->id, 5);

    $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 3]]));
    $this->reservations->commit('order:1');

    expect(($this->level)($erp, $this->s))->on_hand->toBe(1)->reserved->toBe(0)
        ->and(($this->level)($this->hn, $this->s))->on_hand->toBe(3)->reserved->toBe(0);
});

it('lệnh release-expired giải phóng reservation quá hạn', function () {
    I::stock($this->hn, $this->s->id, 5);
    $this->reservations->reserve(($this->request)('checkout:a', [[$this->s->id, 2]], ttl: 60));
    $this->reservations->reserve(($this->request)('checkout:b', [[$this->s->id, 1]], ttl: 3600));

    $this->travel(5)->minutes();
    $this->artisan('vani:inventory:release-expired')->assertSuccessful();

    expect(StockReservation::query()->pluck('status', 'reservation_key')->map->value->all())->toBe(['checkout:a' => 'released', 'checkout:b' => 'active'])
        ->and(($this->level)($this->hn, $this->s)->reserved)->toBe(1);
});

it('ATS theo kênh cộng các location, trừ giữ hàng và tồn an toàn; strategy chỉ được giảm', function () {
    I::stock($this->hn, $this->s->id, 5, safetyStock: 1);
    I::stock($this->hcm, $this->s->id, 3);
    $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 2]]));

    expect(app(AvailabilityReader::class)->forChannel([$this->s->id, $this->m->id], $this->channel->id))
        ->toBe([$this->s->id => 5, $this->m->id => 0]);

    $greedy = new class implements InventoryStrategy
    {
        public function code(): string
        {
            return 'greedy';
        }

        public function adjust(array $standardAts, int $channelId): array
        {
            return array_map(fn () => 999, $standardAts);
        }
    };
    $this->app->instance('greedy-strategy', $greedy);
    $this->app->make(Extensions::class)->tag(['greedy-strategy'], ChannelAvailability::TAG);
    config(['vanishop.inventory.strategy' => 'greedy']);

    expect(app(AvailabilityReader::class)->forChannel([$this->s->id], $this->channel->id))->toBe([$this->s->id => 5]);
});

it('phát AvailabilityChanged sau commit transaction', function () {
    Event::fake([AvailabilityChanged::class]);
    I::stock($this->hn, $this->s->id, 5);

    $this->reservations->reserve(($this->request)('order:1', [[$this->s->id, 1]]));

    Event::assertDispatched(AvailabilityChanged::class, fn ($event) => $event->variantIds === [$this->s->id]);
});
