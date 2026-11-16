<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';

/*
| Chuyển đổi sản phẩm đơn ⇄ có biến thể không được làm mất lịch sử thương mại: đơn cũ, giữ hàng, bảng giá, sổ tồn, giỏ, đổi trả.
| Đơn → biến thể: SKU giữ nguyên id (mọi dữ liệu gắn theo id còn nguyên). Biến thể → đơn: chỉ khi các SKU bị bỏ chưa phát sinh gì.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.manage', 'inventory.adjust']), 'staff');
    $this->style = T::product(Brand::factory()->create()->id, ['style_code' => 'LM-CVT']);
    $this->base = "/admin/catalog/products/{$this->style->id}";
    $this->location = I::location(['code' => 'WH-MAIN']);
    T::seed(function () {
        $this->ivory = OptionValue::factory()->color()->create(['code' => 'IVR']);
        $this->black = OptionValue::factory()->color()->create(['code' => 'BLK']);
        $this->colorType = (int) OptionType::query()->where('code', OptionType::COLOR)->value('id');
    });
    $this->payload = fn (): array => T::seed(fn () => [
        'style_code' => 'LM-CVT', 'slug' => $this->style->fresh()->slug, 'status' => 'active', 'brand_id' => $this->style->brand_id,
        'lock_version' => $this->style->fresh()->lock_version, 'translations' => ['vi' => ['name' => 'Áo chuyển đổi']],
    ]);
    $this->put = fn (array $variants) => $this->put($this->base, [...($this->payload)(), 'variants' => $variants]);

    /* Lịch sử thương mại gắn với một SKU. */
    $this->order = function (int $variantId, string $color = 'Ngà'): int {
        return T::seed(function () use ($variantId, $color): int {
            $at = now()->subMonths(3);
            $orderId = DB::table('orders')->insertGetId([
                'public_id' => strtoupper((string) Str::ulid()), 'number' => 'CV'.Str::random(6), 'source' => 'web', 'currency_code' => 'VND',
                'order_status' => 'completed', 'payment_status' => 'paid', 'fulfillment_status' => 'delivered', 'payment_method' => 'cod',
                'subtotal_amount' => 300_000, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => 300_000,
                'customer_snapshot' => '{}', 'shipping_address' => '{}', 'shipping_method' => '{}', 'reservation_key' => (string) Str::ulid(),
                'placed_at' => $at, 'created_at' => $at, 'updated_at' => $at,
            ]);

            return DB::table('order_lines')->insertGetId([
                'order_id' => $orderId, 'variant_id' => $variantId, 'sku' => Variant::query()->whereKey($variantId)->value('sku'), 'product_name' => 'Áo chuyển đổi',
                'variant_options' => json_encode([['type' => 'color', 'name' => 'Màu', 'code' => '', 'value' => $color]], JSON_UNESCAPED_UNICODE), 'quantity' => 1, 'cancelled_quantity' => 0, 'unit_amount' => 300_000, 'subtotal_amount' => 300_000, 'discount_amount' => 0,
                'total_amount' => 300_000, 'tax_rate_bp' => 0, 'tax_amount' => 0, 'created_at' => $at, 'updated_at' => $at,
            ]);
        });
    };
    $this->reservation = fn (int $variantId, string $status = 'active') => T::seed(fn () => DB::table('stock_reservations')->insert([
        'reservation_key' => 'checkout:'.Str::random(8), 'location_id' => $this->location->id, 'variant_id' => $variantId, 'quantity' => 1,
        'status' => $status, 'expires_at' => now()->addMinutes(15), 'release_reason' => $status === 'released' ? 'expired' : null, 'created_at' => now(), 'updated_at' => now(),
    ]));
    $this->salePrice = fn (int $variantId) => T::seed(function () use ($variantId): void {
        $listId = DB::table('price_lists')->where('code', 'sale-11')->value('id') ?? DB::table('price_lists')->insertGetId([
            'code' => 'sale-11', 'name' => 'Sale 11.11', 'type' => 'sale', 'priority' => 10, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('prices')->insert(['price_list_id' => $listId, 'variant_id' => $variantId, 'amount' => 199_000, 'min_qty' => 1, 'created_at' => now(), 'updated_at' => now()]);
    });
    /* Sổ tồn ở nhiều thời điểm: nhập, bán bớt, kiểm kê. */
    $this->stockHistory = function (int $variantId): void {
        foreach ([[now()->subMonths(2), 10], [now()->subMonth(), -3], [now()->subDay(), -2]] as [$at, $delta]) {
            $this->travelTo($at);
            T::seed(fn () => app(StockAdjustmentService::class)->adjust($this->location, $variantId, $delta, 'Lịch sử'));
        }
        $this->travelBack();
    };
    $this->cart = fn (int $variantId) => T::seed(function () use ($variantId): void {
        $cartId = DB::table('carts')->insertGetId([
            'public_id' => strtoupper((string) Str::ulid()), 'token_hash' => hash('sha256', Str::random()), 'currency_code' => 'VND', 'status' => 'active',
            'last_activity_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cart_lines')->insert(['cart_id' => $cartId, 'variant_id' => $variantId, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()]);
    });
    /* Khách xin đổi sang SKU này (chưa có đơn đổi): chỉ dòng đổi trả tham chiếu SKU. */
    $this->exchange = function (int $variantId): void {
        $lineId = ($this->order)(T::seed(fn () => app(ProductOptions::class)->createVariant(T::product(null, ['style_code' => 'OTHER-'.Str::random(4)]), [], ['sku' => 'OTHER-'.Str::random(4)])->id));
        T::seed(function () use ($lineId, $variantId): void {
            $returnId = DB::table('return_requests')->insertGetId([
                'public_id' => strtoupper((string) Str::ulid()), 'number' => 'CV-R'.Str::random(4), 'order_id' => DB::table('order_lines')->where('id', $lineId)->value('order_id'),
                'status' => 'requested', 'reason_code' => 'size', 'source' => 'customer', 'refund_amount' => 0, 'currency_code' => 'VND', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('return_lines')->insert([
                'return_request_id' => $returnId, 'order_line_id' => $lineId, 'variant_id' => DB::table('order_lines')->where('id', $lineId)->value('variant_id'),
                'exchange_variant_id' => $variantId, 'quantity' => 1, 'refund_amount' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    };

    /* Sản phẩm có hai màu (IVR giữ lại, BLK bỏ đi) và sản phẩm đơn. */
    $this->twoColors = function (): array {
        ($this->put)(['axes' => [['option_type_id' => $this->colorType, 'value_ids' => [$this->ivory->id, $this->black->id]]], 'cells' => [
            ['values' => [$this->ivory->id], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT-IVR', 'fields' => ['pricing' => ['price' => 300_000]]],
            ['values' => [$this->black->id], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT-BLK', 'fields' => ['pricing' => ['price' => 300_000]]],
        ]])->assertSessionHasNoErrors();

        return T::seed(fn () => Variant::query()->pluck('id', 'sku')->map(fn ($id): int => (int) $id)->all());
    };
    $this->collapse = fn (int $keep) => ($this->put)(['axes' => [], 'collapse_to' => $keep, 'cells' => [
        ['values' => [], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT', 'fields' => ['pricing' => ['price' => 280_000]]],
    ]]);
});

it('đơn → có biến thể: SKU có đơn cũ, đang giữ hàng, nằm trong bảng giá, sổ tồn nhiều thời điểm, trong giỏ — giữ nguyên tất cả', function () {
    ($this->put)(['axes' => [], 'cells' => [['values' => [], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT', 'fields' => ['pricing' => ['price' => 300_000]]]]])->assertSessionHasNoErrors();
    $id = T::seed(fn () => (int) Variant::query()->sole()->id);
    $orderLine = ($this->order)($id);
    ($this->stockHistory)($id);
    ($this->reservation)($id);
    T::seed(fn () => DB::table('stock_levels')->where('variant_id', $id)->update(['reserved' => 1]));
    ($this->salePrice)($id);
    ($this->cart)($id);
    $before = T::seed(fn () => [
        'line' => (array) DB::table('order_lines')->find($orderLine),
        'movements' => DB::table('stock_movements')->where('variant_id', $id)->orderBy('id')->get(['on_hand_delta', 'on_hand_after', 'created_at'])->toArray(),
        'level' => (array) DB::table('stock_levels')->where('variant_id', $id)->first(['on_hand', 'reserved']),
        'prices' => DB::table('prices')->where('variant_id', $id)->orderBy('price_list_id')->pluck('amount')->all(),
    ]);

    ($this->put)(['axes' => [['option_type_id' => $this->colorType, 'value_ids' => [$this->ivory->id, $this->black->id]]], 'adopt_single' => [$this->ivory->id], 'cells' => [
        ['values' => [$this->ivory->id], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT', 'fields' => []],
        ['values' => [$this->black->id], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT-BLK', 'fields' => []],
    ]])->assertSessionHasNoErrors();

    T::seed(function () use ($id, $orderLine, $before): void {
        $variant = Variant::query()->with('optionValues')->findOrFail($id);
        expect($variant->sku)->toBe('LM-CVT')
            ->and($variant->optionValues->pluck('id')->all())->toBe([$this->ivory->id])
            ->and((array) DB::table('order_lines')->find($orderLine))->toBe($before['line'])
            ->and(DB::table('stock_movements')->where('variant_id', $id)->orderBy('id')->get(['on_hand_delta', 'on_hand_after', 'created_at'])->toArray())->toEqual($before['movements'])
            ->and($before['movements'])->toHaveCount(3)
            ->and((array) DB::table('stock_levels')->where('variant_id', $id)->first(['on_hand', 'reserved']))->toBe($before['level'])
            ->and(DB::table('stock_reservations')->where('variant_id', $id)->value('status'))->toBe('active')
            ->and(DB::table('prices')->where('variant_id', $id)->orderBy('price_list_id')->pluck('amount')->all())->toBe($before['prices'])
            ->and(DB::table('cart_lines')->where('variant_id', $id)->exists())->toBeTrue()
            ->and(Variant::query()->count())->toBe(2);
    });
});

it('có biến thể → đơn: SKU còn lại chưa phát sinh thì bị xoá (dọn giá niêm yết); SKU giữ lại giữ id và mọi lịch sử', function () {
    $ids = ($this->twoColors)();
    $keep = $ids['LM-CVT-IVR'];
    $orderLine = ($this->order)($keep);
    ($this->stockHistory)($keep);
    ($this->salePrice)($keep);

    $this->get("{$this->base}/edit")->assertInertia(fn (Assert $page) => $page->where('variantMatrix.used', [$keep]));
    ($this->collapse)($keep)->assertSessionHasNoErrors();

    T::seed(function () use ($keep, $ids, $orderLine): void {
        $kept = Variant::query()->with('optionValues')->sole();
        expect($kept->id)->toBe($keep)->and($kept->sku)->toBe('LM-CVT')->and($kept->optionValues)->toBeEmpty()
            ->and($this->style->options()->count())->toBe(0)->and($this->style->optionValues()->count())->toBe(0)
            ->and(DB::table('order_lines')->find($orderLine))->variant_id->toBe($keep)->variant_options->toContain('Ngà')
            ->and(DB::table('stock_movements')->where('variant_id', $keep)->count())->toBe(3)
            ->and(DB::table('prices')->where('variant_id', $keep)->count())->toBe(2)
            ->and(DB::table('prices')->where('variant_id', $ids['LM-CVT-BLK'])->exists())->toBeFalse();
    });
    $this->get("{$this->base}/edit")->assertInertia(fn (Assert $page) => $page->where('product.variants.0.values', [])->where('variantMatrix.axes', []));

    // Sau khi về sản phẩm đơn, chuyển lại sang có biến thể vẫn giữ chính SKU đó.
    ($this->put)(['axes' => [['option_type_id' => $this->colorType, 'value_ids' => [$this->black->id]]], 'adopt_single' => [$this->black->id], 'cells' => [
        ['values' => [$this->black->id], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT', 'fields' => []],
    ]])->assertSessionHasNoErrors();
    expect(T::seed(fn () => Variant::query()->sole()->id))->toBe($keep);
});

it('có biến thể → đơn bị từ chối khi SKU bị bỏ đã phát sinh — không đổi gì', function (string $history) {
    $ids = ($this->twoColors)();
    $drop = $ids['LM-CVT-BLK'];
    match ($history) {
        'đơn hàng cũ' => ($this->order)($drop, 'Đen'),
        'đang giữ hàng' => ($this->reservation)($drop),
        'giữ hàng đã nhả' => ($this->reservation)($drop, 'released'),
        'bảng giá khuyến mãi' => ($this->salePrice)($drop),
        'sổ tồn nhiều thời điểm' => ($this->stockHistory)($drop),
        'trong giỏ' => ($this->cart)($drop),
        'khách xin đổi sang' => ($this->exchange)($drop),
    };
    $state = fn (): array => T::seed(fn () => [
        Variant::query()->with('optionValues')->orderBy('id')->get()->map(fn (Variant $variant): array => [$variant->id, $variant->sku, $variant->optionValues->pluck('id')->all()])->all(),
        $this->style->options()->count(), DB::table('prices')->count(),
    ]);
    $before = $state();

    $this->get("{$this->base}/edit")->assertInertia(fn (Assert $page) => $page->where('variantMatrix.used', [$drop]));
    ($this->collapse)($ids['LM-CVT-IVR'])->assertSessionHasErrors(['collapse_to' => __('catalog::messages.collapse_used', ['skus' => 'LM-CVT-BLK'])]);

    expect($state())->toBe($before);
})->with(['đơn hàng cũ', 'đang giữ hàng', 'giữ hàng đã nhả', 'bảng giá khuyến mãi', 'sổ tồn nhiều thời điểm', 'trong giỏ', 'khách xin đổi sang']);

it('có biến thể → đơn: SKU đã phát sinh duy nhất được giữ lại (SKU kia chưa phát sinh thì xoá)', function () {
    $ids = ($this->twoColors)();
    ($this->order)($ids['LM-CVT-BLK'], 'Đen');

    ($this->collapse)($ids['LM-CVT-BLK'])->assertSessionHasNoErrors();
    expect(T::seed(fn () => Variant::query()->sole()->id))->toBe($ids['LM-CVT-BLK']);
});

it('collapse_to không hợp lệ: SKU của sản phẩm khác, còn gửi trục, hoặc sản phẩm chưa có trục → từ chối', function () {
    $ids = ($this->twoColors)();
    $foreign = T::seed(fn () => app(ProductOptions::class)->createVariant(T::product(null, ['style_code' => 'OTHER']), [], ['sku' => 'OTHER'])->id);

    ($this->collapse)($foreign)->assertSessionHasErrors('collapse_to');
    ($this->put)(['axes' => [['option_type_id' => $this->colorType, 'value_ids' => [$this->ivory->id]]], 'collapse_to' => $ids['LM-CVT-IVR'], 'cells' => [
        ['values' => [$this->ivory->id], 'enabled' => true, 'status' => 'active', 'sku' => 'LM-CVT-IVR', 'fields' => []],
    ]])->assertSessionHasErrors('collapse_to');
    expect(T::seed(fn () => Variant::query()->where('style_id', $this->style->id)->count()))->toBe(2);

    ($this->collapse)($ids['LM-CVT-IVR'])->assertSessionHasNoErrors();
    ($this->collapse)($ids['LM-CVT-IVR'])->assertSessionHasErrors('collapse_to');
});

it('tắt ô ma trận: SKU chỉ có giữ hàng đã nhả hoặc nằm trong bảng giá khuyến mãi → ngừng bán, không xoá (không lỗi khoá ngoại)', function () {
    $ids = ($this->twoColors)();
    ($this->reservation)($ids['LM-CVT-BLK'], 'released');
    ($this->salePrice)($ids['LM-CVT-IVR']);

    ($this->put)(['axes' => [['option_type_id' => $this->colorType, 'value_ids' => [$this->ivory->id, $this->black->id]]], 'cells' => [
        ['values' => [$this->ivory->id], 'enabled' => false, 'status' => 'active', 'sku' => 'LM-CVT-IVR', 'fields' => []],
        ['values' => [$this->black->id], 'enabled' => false, 'status' => 'active', 'sku' => 'LM-CVT-BLK', 'fields' => []],
    ]])->assertSessionHasNoErrors();

    expect(T::seed(fn () => Variant::query()->orderBy('id')->pluck('status')->map(fn ($status) => $status->value)->all()))->toBe(['inactive', 'inactive']);
});
