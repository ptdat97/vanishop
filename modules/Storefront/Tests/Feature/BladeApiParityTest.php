<?php

use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| L46 — API-first, so khớp dữ liệu thật: trang Blade và Storefront API cho cùng màn hình phải ra cùng số. Đọc các giá trị
| đánh dấu `data-vani-value` (+ `data-vani-amount`, `data-vani-code`) trong view của theme và so với response API tương ứng
| (STOREFRONT_API_READ_PAIRS): giá/giá gạch/giảm %, còn hàng, dòng giỏ và tạm tính, báo giá checkout, tổng đơn và trạng thái.
*/

beforeEach(function () {
    C::autoConfirmCod();
    ['s' => $this->s, 'm' => $this->m] = C::store();
    $this->product = $this->s->product;
    // Giá sale có giá gạch cho size M → khoảng giá, giá gạch, % giảm khác 0.
    P::priceList(['code' => 'sale-parity', 'type' => 'sale', 'priority' => 10], [$this->m->id => [150_000, 200_000]]);
    $this->category = T::seed(fn () => Category::factory()->create(['slug' => 'dam-parity']));
    $this->category->syncTranslations(['vi' => ['name' => 'Đầm']]);
    $this->product->categories()->sync([$this->category->id]);
    $this->api = '/api/storefront/v1';
});

/**
 * Giá trị đánh dấu trong HTML (trong phạm vi `$scope` — XPath — nếu có).
 *
 * @return list<array{amount: ?string, max: ?string, code: ?string, line: ?string, text: string}>
 */
function markedValues(string $html, string $name, string $scope = ''): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $nodes = (new DOMXPath($document))->query("{$scope}//*[@data-vani-value='{$name}']");
    $values = [];
    foreach ($nodes === false ? [] : $nodes as $node) {
        assert($node instanceof DOMElement);
        $values[] = [
            'amount' => $node->hasAttribute('data-vani-amount') ? $node->getAttribute('data-vani-amount') : null,
            'max' => $node->hasAttribute('data-vani-amount-max') ? $node->getAttribute('data-vani-amount-max') : null,
            'code' => $node->hasAttribute('data-vani-code') ? $node->getAttribute('data-vani-code') : null,
            'line' => $node->hasAttribute('data-vani-line') ? $node->getAttribute('data-vani-line') : null,
            'text' => trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? ''),
        ];
    }

    return $values;
}

/** Số tiền đầu tiên đánh dấu `$name` (null khi trang không có). */
function markedAmount(string $html, string $name, string $scope = ''): ?int
{
    $value = markedValues($html, $name, $scope)[0]['amount'] ?? null;

    return $value === null || $value === '' ? null : (int) $value;
}

it('trang sản phẩm ↔ GET /products/{slug}: khoảng giá, giá gạch, % giảm, còn hàng', function () {
    $html = $this->get("/san-pham/{$this->product->slug}")->assertOk()->getContent();
    $api = $this->getJson("{$this->api}/products/{$this->product->slug}")->assertOk()->json('data');
    $scope = '//*[@data-product-price]';

    expect(markedAmount($html, 'price.min', $scope))->toBe($api['price']['min']['amount'])
        ->and((int) markedValues($html, 'price.min', $scope)[0]['max'])->toBe($api['price']['max']['amount'])
        ->and(markedAmount($html, 'price.compare_at', $scope))->toBe($api['price']['compare_at']['amount'] ?? null)
        ->and(markedAmount($html, 'price.discount_percent', $scope))->toBe($api['price']['discount_percent'] ?: null)
        ->and(markedValues($html, 'in_stock')[0]['code'])->toBe($api['in_stock'] ? '1' : '0')
        ->and($api['price']['compare_at'])->not->toBeNull() // dữ liệu mẫu có giá gạch thật
        // Chữ khách thấy cũng là số của API (không chỉ thuộc tính đánh dấu).
        ->and(markedValues($html, 'price.min', $scope)[0]['text'])->toContain($api['price']['min']['formatted'])
        ->and(markedValues($html, 'price.compare_at', $scope)[0]['text'])->toBe($api['price']['compare_at']['formatted']);
});

it('trang danh mục ↔ GET /products?category=: giá từng thẻ sản phẩm', function () {
    T::product(null, ['name' => 'Áo chưa có giá', 'category_ids' => [$this->category->id]]);
    $html = $this->get('/danh-muc/dam-parity')->assertOk()->getContent();
    $items = $this->getJson("{$this->api}/products?category=dam-parity")->assertOk()->json('data');

    expect($items)->not->toBeEmpty();
    foreach ($items as $item) {
        $scope = "//*[@data-vani-product='{$item['slug']}']";
        expect(markedAmount($html, 'price.min', $scope))->toBe($item['price']['min']['amount'] ?? null, $item['slug'])
            ->and(markedAmount($html, 'price.compare_at', $scope))->toBe($item['price']['compare_at']['amount'] ?? null, $item['slug']);
    }
});

it('trang giỏ ↔ GET /carts/{id}; trang thanh toán ↔ POST /checkout/{cart}/quote; trang đơn ↔ GET /orders/{id}', function () {
    // Mọi con số khác nhau (tạm tính ≠ tổng): khuyến mãi tự động 10%, có phí giao (ngưỡng miễn phí rất cao).
    C::promotion(['name' => 'Giảm 10% tự động']);
    T::seed(fn () => app(Settings::class)->set('vani.shipping-flat-rate', 'free_over', 100_000_000));
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->post('/gio-hang', ['variant_id' => $this->m->id, 'quantity' => 2]);
    ['id' => $cartId, 'token' => $cartToken] = session('vani.cart');
    $cartHeaders = ['X-Vani-Cart-Token' => $cartToken];

    $cartHtml = $this->get('/gio-hang')->assertOk()->getContent();
    $cart = $this->getJson("{$this->api}/carts/{$cartId}", $cartHeaders)->assertOk()->json('data');
    expect(markedAmount($cartHtml, 'cart.selected_subtotal'))->toBe($cart['selected_subtotal']['amount']);
    $bladeLines = collect(markedValues($cartHtml, 'line.total'))->mapWithKeys(fn (array $value) => [(string) $value['line'] => (int) $value['amount']])->all();
    expect($bladeLines)->toBe(collect($cart['lines'])->mapWithKeys(fn (array $line) => [(string) $line['id'] => $line['line_total']['amount']])->all());

    $checkoutHtml = $this->get('/thanh-toan')->assertOk()->getContent();
    $quote = $this->postJson("{$this->api}/checkout/{$cartId}/quote", [], $cartHeaders)->assertOk()->json('data');
    foreach (['subtotal', 'shipping_fee', 'total'] as $field) {
        expect(markedAmount($checkoutHtml, "quote.{$field}"))->toBe($quote[$field]['amount'], $field);
    }
    expect(markedValues($checkoutHtml, 'quote.total')[0]['text'])->toBe($quote['total']['formatted']);
    expect(markedAmount($checkoutHtml, 'quote.discount'))->toBe($quote['discount']['amount'])
        ->and($quote['discount']['amount'])->toBeGreaterThan(0)->and($quote['shipping_fee']['amount'])->toBeGreaterThan(0);

    $placed = $this->post('/thanh-toan', [
        'contact' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912345678', 'email' => ''],
        'shipping_address' => ['province_code' => '29', 'ward_code' => '70101065', 'street_line' => '12 Lê Lợi'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => $quote['total']['amount'], 'idempotency_key' => 'parity-order-0001',
    ])->assertRedirect();
    $orderId = basename((string) parse_url((string) $placed->headers->get('Location'), PHP_URL_PATH));
    $orderHtml = $this->get("/don-hang/{$orderId}")->assertOk()->getContent();
    $order = $this->getJson("{$this->api}/orders/{$orderId}", ['X-Vani-Order-Token' => session("vani.orders.{$orderId}.token")])->assertOk()->json('data');

    foreach (['subtotal', 'shipping_fee', 'total'] as $field) {
        expect(markedAmount($orderHtml, "order.{$field}"))->toBe($order[$field]['amount'], $field);
    }
    // Đơn có bảng phân rã giá: trang in từng dòng giảm (dòng 0đ không in), API trả đủ.
    foreach (['list_amount', 'markdown', 'promotion_discount', 'voucher_discount', 'other_discount'] as $field) {
        $amount = $order['pricing'][$field]['amount'];
        expect(markedAmount($orderHtml, "order.pricing.{$field}"))->toBe($field === 'list_amount' || $amount > 0 ? ($field === 'list_amount' && $order['pricing']['markdown']['amount'] === 0 ? null : $amount) : null, $field);
    }
    expect($order['pricing']['promotion_discount']['amount'])->toBeGreaterThan(0);
    expect(markedValues($orderHtml, 'order.total')[0]['text'])->toBe($order['total']['formatted'])
        ->and(markedValues($cartHtml, 'cart.selected_subtotal')[0]['text'])->toBe($cart['selected_subtotal']['formatted']);
    expect(markedValues($orderHtml, 'order.status')[0]['code'])->toBe($order['order_status'])
        ->and($order['total']['amount'])->toBe($quote['total']['amount']);
});
