<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Reports\Infrastructure\Reports\ReorderSuggestionsReport;
use Plugin\Reports\Infrastructure\Reports\SlowMoversReport;
use Plugin\Reports\ReportsServiceProvider;

require_once __DIR__.'/../../../../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';
require_once __DIR__.'/../../../../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Gợi ý nhập lại và hàng bán chậm (reference-parity L41): tốc độ bán = số bán trong kỳ / số ngày của kỳ.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function (): void {
        app(PluginManager::class)->install(ReportsServiceProvider::ID);
        app(PluginManager::class)->enable(ReportsServiceProvider::ID);
    });
    app()->register(ReportsServiceProvider::class);
    $location = I::location(['code' => 'K1']);
    [$this->a, $this->b, $this->c, $this->d] = array_map(fn (string $code) => P::variants(T::product(null, ['product_code' => $code]), ['M'])[0], ['SP-A', 'SP-B', 'SP-C', 'SP-D']);
    foreach ([[$this->a, 3], [$this->b, 50], [$this->c, 7], [$this->d, 30]] as [$variant, $stock]) {
        I::stock($location, $variant->id, $stock);
    }
    app(IncomingStock::class)->set($this->a->id, 2, null, 'test');
    foreach ([[$this->a, 10], [$this->b, 2], [$this->d, 10]] as [$variant, $quantity]) {
        $orderId = DB::table('orders')->insertGetId(['public_id' => strtoupper(Str::ulid()), 'number' => 'SR'.$variant->id, 'source' => 'web', 'currency_code' => 'VND',
            'order_status' => 'completed', 'payment_status' => 'cod_collected', 'fulfillment_status' => 'delivered', 'payment_method' => 'cod',
            'subtotal_amount' => 0, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => 0,
            'customer_snapshot' => '{}', 'shipping_address' => '{}', 'shipping_method' => '{}', 'reservation_key' => 'sr'.$variant->id,
            'placed_at' => '2026-10-05 03:00:00', 'created_at' => '2026-10-05 03:00:00', 'updated_at' => '2026-10-05 03:00:00']);
        DB::table('order_lines')->insert(['order_id' => $orderId, 'variant_id' => $variant->id, 'sku' => $variant->sku, 'product_name' => 'Áo', 'quantity' => $quantity,
            'unit_amount' => 100, 'subtotal_amount' => 100, 'discount_amount' => 0, 'total_amount' => 100, 'tax_rate_bp' => 0, 'tax_amount' => 0,
            'created_at' => '2026-10-05 03:00:00', 'updated_at' => '2026-10-05 03:00:00']);
    }
    $zone = new DateTimeZone('Asia/Ho_Chi_Minh');
    $this->period = new ReportPeriod(new DateTimeImmutable('2026-10-01', $zone), new DateTimeImmutable('2026-10-11', $zone), 'Asia/Ho_Chi_Minh'); // 10 ngày
});

it('gợi ý nhập lại: đủ bán thêm một kỳ — trừ tồn bán được và hàng đang về', function () {
    $result = app(ReorderSuggestionsReport::class)->run($this->period);

    expect($result->table->rows)->toHaveCount(1)
        ->and($result->table->rows[0])->toMatchArray(['sku' => $this->a->sku, 'sold' => 10, 'per_day' => 1.0, 'sellable' => 3, 'incoming' => 2, 'suggested' => 5])
        ->and($result->summary[1]->value)->toBe(5);
});

it('hàng bán chậm: không bán được xếp đầu, rồi số ngày đủ bán; SKU bán đủ nhanh không có mặt', function () {
    $rows = app(SlowMoversReport::class)->run($this->period)->table->rows;

    expect(array_column($rows, 'sku'))->toBe([$this->c->sku, $this->b->sku])
        ->and($rows[0]['cover'])->toBe('Không bán được')
        ->and($rows[1])->toMatchArray(['sellable' => 50, 'sold' => 2, 'cover' => '250']);
});
