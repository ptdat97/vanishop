<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Facades\Hook;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Danh sách sản phẩm cho vận hành SME (0.3.103): cột Giá bán (Pricing) + Tồn bán được (Inventory) qua slot của Catalog;
| hàng loạt Mở bán / Ngừng bán (Catalog) và Đổi giá niêm yết (Pricing: đặt giá, ±%, giảm tiền, giá gạch).
*/

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m] = C::store(stock: 2);
    $this->product = $this->s->product_id;
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.view', 'pricing.manage', 'inventory.view']), 'staff');
    $this->props = fn (): array => $this->get('/admin/catalog/products')->assertOk()->viewData('page')['props'];
    $this->price = fn ($variant): array => (array) DB::table('prices')->join('price_lists', 'price_lists.id', '=', 'prices.price_list_id')
        ->where('price_lists.code', 'base')->where('prices.variant_id', $variant->id)->first(['amount', 'compare_at_amount']);
});

it('cột Giá bán (khoảng giá theo size) và Tồn bán được; thao tác hàng loạt của Catalog và Pricing', function () {
    $props = ($this->props)();
    expect(collect($props['extensions']['columns'])->pluck('label')->all())->toContain('Giá bán', 'Tồn bán được')
        ->and($props['extensions']['values'][$this->product]['price'])->toBe('200.000 ₫ – 300.000 ₫')
        ->and($props['extensions']['values'][$this->product]['stock'])->toBe('4')
        ->and(collect($props['extensions']['actions'])->pluck('key')->all())->toBe(['activate', 'archive', 'brand', 'delete', 'export', 'price']); // A4: danh mục/bộ sưu tập chỉ hiện khi cửa hàng có

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $props = ($this->props)();
    // Chỉ xem: xuất file được (A4), không có thao tác sửa.
    expect(collect($props['extensions']['actions'])->pluck('key')->all())->toBe(['export'])->and($props['extensions']['columns'])->toBe([]);
});

it('Ngừng bán / Mở bán hàng loạt: đổi trạng thái, ghi audit', function () {
    $this->post('/admin/catalog/products/bulk-status', ['ids' => [$this->product], 'status' => 'archived'])->assertRedirect()
        ->assertSessionHas('success', 'Đã chuyển 1 sản phẩm sang "Lưu trữ".');
    expect(DB::table('products')->where('id', $this->product)->value('status'))->toBe('archived')
        ->and(DB::table('audit_logs')->where('action', 'catalog.product.status_changed')->where('subject_id', (string) $this->product)->exists())->toBeTrue();

    $this->post('/admin/catalog/products/bulk-status', ['ids' => [$this->product], 'status' => 'active'])->assertRedirect();
    expect(DB::table('products')->where('id', $this->product)->value('status'))->toBe('active');
    $this->post('/admin/catalog/products/bulk-status', ['ids' => [$this->product], 'status' => 'active'])->assertSessionHas('success', 'Đã chuyển 0 sản phẩm sang "Đang bán".');
});

it('Đổi giá hàng loạt: giảm % có giá gạch, bỏ giá gạch, đặt giá mới; giảm quá giá bị từ chối', function () {
    $url = '/admin/pricing/base-prices/bulk';
    $this->post($url, ['ids' => [$this->product], 'mode' => 'percent_down', 'value' => 10, 'strike' => 1])->assertRedirect()
        ->assertSessionHas('success', 'Đã đổi giá 2 biến thể của 1 sản phẩm; bỏ qua 0 biến thể chưa có giá.');
    expect(($this->price)($this->s))->toBe(['amount' => 270_000, 'compare_at_amount' => 300_000])
        ->and(($this->price)($this->m))->toBe(['amount' => 180_000, 'compare_at_amount' => 200_000])
        ->and(($this->props)()['extensions']['values'][$this->product]['price'])->toBe("180.000 ₫ – 270.000 ₫\ngạch 300.000 ₫");

    // Giảm tiếp: giữ giá gạch gốc (cao nhất).
    $this->post($url, ['ids' => [$this->product], 'mode' => 'amount_down', 'value' => 20_000, 'strike' => 1])->assertRedirect();
    expect(($this->price)($this->s))->toBe(['amount' => 250_000, 'compare_at_amount' => 300_000]);

    $this->post($url, ['ids' => [$this->product], 'mode' => 'clear_compare', 'value' => 0])->assertRedirect();
    expect(($this->price)($this->s))->toBe(['amount' => 250_000, 'compare_at_amount' => null]);

    $this->post($url, ['ids' => [$this->product], 'mode' => 'set', 'value' => 399_000, 'strike' => 0])->assertRedirect();
    expect(($this->price)($this->m))->toBe(['amount' => 399_000, 'compare_at_amount' => null]);

    $this->post($url, ['ids' => [$this->product], 'mode' => 'amount_down', 'value' => 500_000])->assertSessionHasErrors('value');
    $this->post($url, ['ids' => [$this->product], 'mode' => 'percent_down', 'value' => 100])->assertSessionHasErrors('value');
    expect(($this->price)($this->m)['amount'])->toBe(399_000);

    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'pricing.view']), 'staff');
    $this->post($url, ['ids' => [$this->product], 'mode' => 'set', 'value' => 1000])->assertForbidden();
});

it('Đổi giá: xem trước không ghi gì — số SKU, khoảng giá cũ → mới, cảnh báo của plugin (hook), dòng mẫu; nhận tới 2000 sản phẩm', function () {
    Hook::onFilter('vani.pricing.bulk.warnings', fn (array $warnings, int $variantId, ?int $listAmount, int $amount): array => $amount < 200_000 ? [...$warnings, 'Dưới giá vốn'] : $warnings);
    $payload = ['ids' => [$this->product], 'mode' => 'percent_down', 'value' => 10, 'strike' => 1];

    $preview = $this->postJson('/admin/pricing/base-prices/bulk/preview', $payload)->assertOk();
    expect($preview->json('message'))->toBe('1 sản phẩm, 2 SKU: giá niêm yết 200.000 ₫ – 300.000 ₫ → 180.000 ₫ – 270.000 ₫.')
        ->and($preview->json('warning'))->toBeTrue()
        ->and($preview->json('details'))->toContain('Cảnh báo "Dưới giá vốn": 1 SKU')->toContain($this->s->sku.': 300.000 ₫ → 270.000 ₫')
        ->and(($this->price)($this->s)['amount'])->toEqual(300_000);
    expect(collect(($this->props)()['extensions']['actions'])->firstWhere('key', 'price')['preview_url'])->toBe(url('/admin/pricing/base-prices/bulk/preview'));

    $this->postJson('/admin/pricing/base-prices/bulk/preview', [...$payload, 'value' => 100])->assertStatus(422);
    $this->post('/admin/pricing/base-prices/bulk', [...$payload, 'ids' => [$this->product, ...range(900_001, 900_250)]])->assertSessionHasNoErrors();
    expect(($this->price)($this->s)['amount'])->toEqual(270_000);
});

it('"Chọn tất cả kết quả lọc": id mọi sản phẩm khớp bộ lọc (không theo trang), chỉ cần quyền xem catalog', function () {
    $other = T::product(null, ['product_code' => 'KHAC01', 'name' => 'Khác']);

    $this->getJson('/admin/catalog/products/ids?brand=none')->assertOk()->assertExactJson(['ids' => [$other->id], 'total' => 1, 'capped' => false]);
    expect($this->getJson('/admin/catalog/products/ids')->json('total'))->toBe(2);
    $this->actingAs(T::staff(['admin.access']), 'staff');
    $this->getJson('/admin/catalog/products/ids')->assertForbidden();
});
