<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Persistence\Models\StockReceipt;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/InventoryTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A6: phiếu nhập nhanh — nháp (quét/dán/file), nhập kho cộng tồn một lần (biến động `receive` mang số phiếu), trừ
| hàng đang về, huỷ; phiếu đã nhập không sửa/nhập lại được.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'inventory.view', 'inventory.adjust']), 'staff');
    $this->location = I::location(['code' => 'KHO-CHINH']);
    [$this->s, $this->m] = P::variants(T::product(null, ['style_code' => 'DAM01']));
    I::stock($this->location, $this->s->id, 2);
    $this->onHand = fn ($variant): int => (int) DB::table('stock_levels')->where('location_id', $this->location->id)->where('variant_id', $variant->id)->value('on_hand');
    $this->payload = fn (array $lines, bool $receive = false): array => [
        'location_id' => $this->location->id, 'supplier' => 'Xưởng May An', 'note' => 'Lô tháng 11', 'receive' => $receive,
        'lines' => array_map(fn (array $line): array => ['variant_id' => $line[0]->id, 'quantity' => $line[1]], $lines),
    ];
});

it('lưu nháp: chưa cộng tồn, có số phiếu PN<yymm>-0001; sửa nháp thay dòng', function () {
    $this->post('/admin/inventory/receipts', ($this->payload)([[$this->s, 5], [$this->m, 3]]))->assertRedirect()->assertSessionHas('success');
    $receipt = StockReceipt::query()->sole();
    expect($receipt->number)->toMatch('/^PN\d{4}-0001$/')->and($receipt->status)->toBe('draft')->and($receipt->supplier)->toBe('Xưởng May An')
        ->and(($this->onHand)($this->s))->toBe(2);

    $this->put("/admin/inventory/receipts/{$receipt->id}", [...($this->payload)([[$this->s, 7]]), 'lock_version' => $receipt->lock_version])->assertSessionHasNoErrors();
    expect($receipt->lines()->pluck('quantity', 'variant_id')->all())->toBe([$this->s->id => 7]);
    // Người khác vừa sửa (phiên bản cũ) → báo, không ghi đè.
    $this->put("/admin/inventory/receipts/{$receipt->id}", [...($this->payload)([[$this->s, 9]]), 'lock_version' => $receipt->lock_version])->assertSessionHasErrors('lock_version');
    expect($receipt->lines()->value('quantity'))->toBe(7);
    $this->get("/admin/inventory/receipts/{$receipt->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Inventory::Receipts/Form')->where('receipt.lines.0.sku', $this->s->sku)->where('receipt.lines.0.quantity', 7));
});

it('nhập kho: cộng tồn một lần, biến động receive mang số phiếu, trừ hàng đang về; nhập lại / sửa / huỷ phiếu đã nhập bị từ chối', function () {
    app(IncomingStock::class)->set($this->s->id, 10, null, 'staff');
    $this->post('/admin/inventory/receipts', ($this->payload)([[$this->s, 5], [$this->m, 3]], receive: true))->assertRedirect();
    $receipt = StockReceipt::query()->sole();

    expect($receipt->status)->toBe('received')->and($receipt->received_at)->not->toBeNull()
        ->and(($this->onHand)($this->s))->toBe(7)
        ->and(($this->onHand)($this->m))->toBe(3)
        ->and(DB::table('stock_movements')->where('reference', $receipt->number)->where('type', 'receive')->pluck('on_hand_delta')->sort()->values()->all())->toEqual([3, 5])
        ->and(app(IncomingStock::class)->forVariants([$this->s->id])[$this->s->id]->quantity)->toBe(5);

    $this->post("/admin/inventory/receipts/{$receipt->id}/receive")->assertSessionHasErrors('receipt');
    $this->put("/admin/inventory/receipts/{$receipt->id}", [...($this->payload)([[$this->s, 1]]), 'lock_version' => $receipt->lock_version])->assertSessionHasErrors('receipt');
    $this->post("/admin/inventory/receipts/{$receipt->id}/cancel")->assertSessionHasErrors('receipt');
    expect(($this->onHand)($this->s))->toBe(7);
});

it('huỷ nháp: không cộng tồn, không nhập kho được nữa', function () {
    $this->post('/admin/inventory/receipts', ($this->payload)([[$this->s, 5]]));
    $receipt = StockReceipt::query()->sole();
    $this->post("/admin/inventory/receipts/{$receipt->id}/cancel")->assertSessionHasNoErrors();
    $this->post("/admin/inventory/receipts/{$receipt->id}/receive")->assertSessionHasErrors('receipt');
    expect($receipt->fresh()->status)->toBe('cancelled')->and(($this->onHand)($this->s))->toBe(2);
});

it('đọc dòng từ quét/dán (SKU lặp cộng dồn, "SKU 5", không phân biệt hoa thường) và từ file; SKU lạ/số sai được báo', function () {
    $text = implode("\n", [$this->s->sku, strtolower($this->s->sku), "{$this->m->sku} 5", "{$this->m->sku}, 2", 'KHONG-CO, 1', "{$this->s->sku}, -3"]);
    $parsed = $this->postJson('/admin/inventory/receipts/parse', ['text' => $text])->assertOk();
    expect(collect($parsed->json('lines'))->pluck('quantity', 'sku')->all())->toBe([$this->s->sku => 2, $this->m->sku => 7])
        ->and($parsed->json('errors'))->toHaveCount(2);

    $file = UploadedFile::fake()->createWithContent('nhap.csv', "SKU,Số lượng\n{$this->s->sku},4\n");
    expect($this->post('/admin/inventory/receipts/parse', ['file' => $file], ['Accept' => 'application/json'])->assertOk()->json('lines.0.quantity'))->toBe(4);
});

it('dữ liệu sai: không dòng, kho không quản lý tồn; chỉ xem tồn thì không tạo/nhập được', function () {
    $this->post('/admin/inventory/receipts', [...($this->payload)([[$this->s, 1]]), 'lines' => []])->assertSessionHasErrors('lines');
    $external = I::location(['stock_authority' => 'erp']);
    $this->post('/admin/inventory/receipts', [...($this->payload)([[$this->s, 1]]), 'location_id' => $external->id])->assertSessionHasErrors('location_id');

    $this->actingAs(T::staff(['admin.access', 'inventory.view']), 'staff');
    $this->get('/admin/inventory/receipts')->assertOk();
    $this->post('/admin/inventory/receipts', ($this->payload)([[$this->s, 1]]))->assertForbidden();
    expect(StockReceipt::query()->count())->toBe(0);
});
