<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Danh sách sản phẩm cho vận hành SME (0.3.103): cột Giá bán (Pricing) + Tồn bán được (Inventory) qua slot của Catalog;
| hàng loạt Mở bán / Ngừng bán (Catalog) và Đổi giá niêm yết (Pricing: đặt giá, ±%, giảm tiền, giá gạch).
*/

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m] = C::store(stock: 2);
    $this->style = $this->s->style_id;
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.view', 'pricing.manage', 'inventory.view']), 'staff');
    $this->props = fn (): array => $this->get('/admin/catalog/products')->assertOk()->viewData('page')['props'];
    $this->price = fn ($variant): array => (array) DB::table('prices')->join('price_lists', 'price_lists.id', '=', 'prices.price_list_id')
        ->where('price_lists.code', 'base')->where('prices.variant_id', $variant->id)->first(['amount', 'compare_at_amount']);
});

it('cột Giá bán (khoảng giá theo size) và Tồn bán được; thao tác hàng loạt của Catalog và Pricing', function () {
    $props = ($this->props)();
    expect(collect($props['extensions']['columns'])->pluck('label')->all())->toContain('Giá bán', 'Tồn bán được')
        ->and($props['extensions']['values'][$this->style]['price'])->toBe('200.000 ₫ – 300.000 ₫')
        ->and($props['extensions']['values'][$this->style]['stock'])->toBe('4')
        ->and(collect($props['extensions']['actions'])->pluck('key')->all())->toBe(['activate', 'archive', 'price']);

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $props = ($this->props)();
    expect($props['extensions']['actions'])->toBe([])->and($props['extensions']['columns'])->toBe([]);
});

it('Ngừng bán / Mở bán hàng loạt: đổi trạng thái, ghi audit', function () {
    $this->post('/admin/catalog/products/bulk-status', ['ids' => [$this->style], 'status' => 'archived'])->assertRedirect()
        ->assertSessionHas('success', 'Đã chuyển 1 sản phẩm sang "Lưu trữ".');
    expect(DB::table('styles')->where('id', $this->style)->value('status'))->toBe('archived')
        ->and(DB::table('audit_logs')->where('action', 'catalog.product.status_changed')->where('subject_id', (string) $this->style)->exists())->toBeTrue();

    $this->post('/admin/catalog/products/bulk-status', ['ids' => [$this->style], 'status' => 'active'])->assertRedirect();
    expect(DB::table('styles')->where('id', $this->style)->value('status'))->toBe('active');
    $this->post('/admin/catalog/products/bulk-status', ['ids' => [$this->style], 'status' => 'active'])->assertSessionHas('success', 'Đã chuyển 0 sản phẩm sang "Đang bán".');
});

it('Đổi giá hàng loạt: giảm % có giá gạch, bỏ giá gạch, đặt giá mới; giảm quá giá bị từ chối', function () {
    $url = '/admin/pricing/base-prices/bulk';
    $this->post($url, ['ids' => [$this->style], 'mode' => 'percent_down', 'value' => 10, 'strike' => 1])->assertRedirect()
        ->assertSessionHas('success', 'Đã đổi giá 2 biến thể của 1 sản phẩm; bỏ qua 0 biến thể chưa có giá.');
    expect(($this->price)($this->s))->toBe(['amount' => 270_000, 'compare_at_amount' => 300_000])
        ->and(($this->price)($this->m))->toBe(['amount' => 180_000, 'compare_at_amount' => 200_000])
        ->and(($this->props)()['extensions']['values'][$this->style]['price'])->toBe("180.000 ₫ – 270.000 ₫\ngạch 300.000 ₫");

    // Giảm tiếp: giữ giá gạch gốc (cao nhất).
    $this->post($url, ['ids' => [$this->style], 'mode' => 'amount_down', 'value' => 20_000, 'strike' => 1])->assertRedirect();
    expect(($this->price)($this->s))->toBe(['amount' => 250_000, 'compare_at_amount' => 300_000]);

    $this->post($url, ['ids' => [$this->style], 'mode' => 'clear_compare', 'value' => 0])->assertRedirect();
    expect(($this->price)($this->s))->toBe(['amount' => 250_000, 'compare_at_amount' => null]);

    $this->post($url, ['ids' => [$this->style], 'mode' => 'set', 'value' => 399_000, 'strike' => 0])->assertRedirect();
    expect(($this->price)($this->m))->toBe(['amount' => 399_000, 'compare_at_amount' => null]);

    $this->post($url, ['ids' => [$this->style], 'mode' => 'amount_down', 'value' => 500_000])->assertSessionHasErrors('value');
    $this->post($url, ['ids' => [$this->style], 'mode' => 'percent_down', 'value' => 100])->assertSessionHasErrors('value');
    expect(($this->price)($this->m)['amount'])->toBe(399_000);

    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'pricing.view']), 'staff');
    $this->post($url, ['ids' => [$this->style], 'mode' => 'set', 'value' => 1000])->assertForbidden();
});
