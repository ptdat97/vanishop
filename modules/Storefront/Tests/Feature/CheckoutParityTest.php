<?php

use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Test song kênh (ADR-044, api-first-refactor đợt A1): cùng giỏ, cùng dữ liệu khách nhập, đặt hàng qua Storefront API và qua
| form checkout của Blade phải cho CÙNG kết quả — có đơn hay không, tổng tiền, giảm giá, lỗi ở trường nào. Hai kênh chỉ khác
| cách gửi (header/token so với phiên/form) và cách trả (JSON so với chuyển hướng), không khác luật.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => null]);
});

/**
 * Kết quả chuẩn hoá để so hai kênh.
 *
 * @return array{placed: bool, total: int|null, discount: int|null, error_fields: list<string>}
 */
function parityOutcome(bool $placed, array $errorFields = []): array
{
    $order = $placed ? Order::query()->withoutGlobalScopes()->latest('id')->first() : null;
    sort($errorFields);

    return ['placed' => $placed, 'total' => $order === null ? null : (int) $order->total_amount, 'discount' => $order === null ? null : (int) $order->discount_amount, 'error_fields' => array_values(array_unique($errorFields))];
}

dataset('checkout scenarios', [
    'đơn thường' => [[], null, 330_000],
    'mã giảm giá hợp lệ' => [[], 'GIAM10', 300_000],
    'mã không tồn tại: bỏ mã, vẫn đặt đơn' => [[], 'KHONGCO', 330_000],
    'SĐT sai' => [['contact' => ['phone' => '123']], null, 330_000],
    'thiếu phường' => [['shipping_address' => ['ward_code' => '']], null, 330_000],
    'tổng khách thấy đã cũ' => [[], null, 1],
]);

it('đặt hàng qua API và qua Blade cho cùng kết quả', function (array $overrides, ?string $voucher, int $expected) {
    // Kênh API: giỏ bằng token, mã giảm giá đặt lên giỏ, đặt hàng bằng JSON.
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    if ($voucher !== null) {
        $this->putJson("/api/storefront/v1/carts/{$cart}/vouchers", ['voucher_codes' => [$voucher]], $headers)->assertOk();
    }
    $api = $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload([...$overrides, 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => 'parity-api-01']);
    $apiOutcome = parityOutcome($api->status() === 201, $api->status() === 201 ? [] : apiErrorFields($api));

    // Kênh Blade: giỏ trong phiên, mã gõ ở ô checkout, đặt hàng bằng form.
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $form = array_replace_recursive(C::orderPayload($overrides), ['expected_total' => $expected, 'idempotency_key' => 'parity-blade-01', 'voucher_code' => $voucher ?? '']);
    $blade = $this->post('/thanh-toan', $form);
    $bag = session('errors');
    // Túi lỗi trong phiên test: ViewErrorBag, hoặc đã tuần tự hoá thành {default: {format, messages}}.
    $errors = $bag instanceof ViewErrorBag ? $bag->getBag('default')->keys() : array_keys((array) ($bag['default']['messages'] ?? []));
    $bladeOutcome = parityOutcome($errors === [] && str_contains((string) $blade->headers->get('Location'), '/don-hang/'), $errors);

    expect($bladeOutcome)->toBe($apiOutcome);
})->with('checkout scenarios');

it('kết quả song kênh đúng như mong đợi (không phải cùng sai)', function () {
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $this->putJson("/api/storefront/v1/carts/{$cart}/vouchers", ['voucher_codes' => ['GIAM10']], $headers)->assertOk();

    $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 300_000]), [...$headers, 'Idempotency-Key' => 'parity-sanity-1'])->assertCreated();
    expect(parityOutcome(true))->toBe(['placed' => true, 'total' => 300_000, 'discount' => 30_000, 'error_fields' => []]);
});

/**
 * Trường lỗi của phản hồi API theo cùng quy ước với form Blade: lỗi có trường → tên trường (tỉnh/phường quy về `*_code` khi
 * có danh mục địa giới); lỗi không gắn trường (tổng đã đổi…) → `business`.
 *
 * @return list<string>
 */
function apiErrorFields(TestResponse $response): array
{
    $fields = array_values(array_filter(array_map(fn (array $issue): ?string => $issue['field'] ?? null, (array) $response->json('error.details.issues'))));
    $fields = array_map(fn (string $field): string => match (true) {
        str_starts_with($field, 'shipping_address.province') => 'shipping_address.province_code',
        str_starts_with($field, 'shipping_address.ward') => 'shipping_address.ward_code',
        default => $field,
    }, $fields);

    return $fields === [] ? ['business'] : $fields;
}
