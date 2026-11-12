<?php

use Illuminate\Routing\Route;
use Illuminate\Testing\TestResponse;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as Cu;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Tests\Support\JsonSchemaSubset;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Hợp đồng /api/storefront/v1 (ADR-044, api-first-refactor đợt A4): OpenAPI docs/api/openapi/storefront-v1.json khớp
| route thật, và phản hồi thật (thành công + lỗi) khớp schema mà OpenAPI tham chiếu. Thêm/đổi endpoint hoặc đổi hình
| dạng phản hồi mà không cập nhật OpenAPI/schema → đỏ. Route `x/<plugin>` do plugin tự mô tả.
*/

/** Tập từ khoá mà Tests\Support\JsonSchemaSubset kiểm được — schema dùng từ khoá khác sẽ không được kiểm thật. */
const SUPPORTED_SCHEMA_KEYWORDS = ['$schema', '$id', 'title', 'description', 'type', 'required', 'properties', 'additionalProperties', 'items', 'enum', 'const', 'pattern', 'minimum', 'format', '$ref'];

function storefrontOpenApi(): array
{
    return json_decode((string) file_get_contents(base_path('docs/api/openapi/storefront-v1.json')), true, flags: JSON_THROW_ON_ERROR);
}

/** Schema phản hồi (file JSON) OpenAPI khai báo cho path + method + status; null khi status không có nội dung. */
function storefrontResponseSchema(string $path, string $method, int $status): ?string
{
    $spec = storefrontOpenApi();
    $response = $spec['paths'][$path][strtolower($method)]['responses'][(string) $status] ?? null;
    expect($response)->not->toBeNull("OpenAPI thiếu {$method} {$path} → {$status}");
    if (isset($response['$ref'])) {
        $response = $spec['components']['responses'][basename($response['$ref'])];
    }
    $ref = $response['content']['application/json']['schema']['$ref'] ?? null;

    return $ref === null ? null : base_path('docs/api/openapi/'.$ref);
}

function assertStorefrontContract(TestResponse $response, string $path, string $method): TestResponse
{
    $schema = storefrontResponseSchema($path, $method, $response->status());
    if ($schema === null) {
        expect($response->getContent())->toBe('', "{$method} {$path} {$response->status()}: OpenAPI khai báo không có nội dung");

        return $response;
    }
    $errors = JsonSchemaSubset::validate($response->json(), $schema);
    expect($errors)->toBe([], "{$method} {$path} {$response->status()}: ".implode('; ', $errors));

    return $response;
}

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s, 'm' => $this->m] = C::store();
    $category = T::seed(fn () => Category::factory()->create(['slug' => 'dam-vay']));
    $category->syncTranslations(['vi' => ['name' => 'Đầm váy']]);
    $this->api = Cu::API;
    Cu::fakeOtp();
});

it('mọi route /api/storefront/v1 của Core có trong OpenAPI (đúng method) và ngược lại', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'api.storefront.v1.'))
        ->flatMap(fn (Route $route) => collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')
            ->map(fn (string $method): string => $method.' '.preg_replace(['#^api/storefront/v1#', '#\{(\w+)\?\}#'], ['', '{$1}'], $route->uri())))
        ->sort()->values()->all();
    $documented = collect(storefrontOpenApi()['paths'])->flatMap(fn (array $operations, string $path) => collect(array_keys($operations))
        ->map(fn (string $method): string => strtoupper($method).' '.$path))->sort()->values()->all();

    expect($documented)->toBe($routes);
});

it('mọi schema OpenAPI tham chiếu tồn tại và chỉ dùng từ khoá test kiểm được', function () {
    $refs = [];
    $spec = storefrontOpenApi();
    array_walk_recursive($spec, function (mixed $value, string|int $key) use (&$refs): void {
        if ($key === '$ref' && str_starts_with((string) $value, '../')) {
            $refs[] = (string) $value;
        }
    });
    $files = [...array_unique($refs), ...array_map(fn (string $file): string => '../schemas/storefront/'.basename($file), glob(base_path('docs/api/schemas/storefront/*.json')))];

    foreach (array_unique($files) as $ref) {
        $file = base_path('docs/api/openapi/'.$ref);
        expect(is_file($file))->toBeTrue("Thiếu schema {$ref}");
        $unsupported = [];
        $walk = function (array $schema, string $at) use (&$walk, &$unsupported, $ref): void {
            foreach (array_diff(array_keys($schema), SUPPORTED_SCHEMA_KEYWORDS) as $keyword) {
                $unsupported[] = "{$ref} {$at}: {$keyword}";
            }
            foreach ($schema['properties'] ?? [] as $name => $property) {
                $walk($property, "{$at}.{$name}");
            }
            if (is_array($schema['items'] ?? null)) {
                $walk($schema['items'], "{$at}[]");
            }
        };
        $walk(json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR), '$');
        expect($unsupported)->toBe([]);
    }
    expect($refs)->not->toBeEmpty();
});

it('khách vãng lai: vỏ trang, catalog, địa chỉ, giỏ, checkout, đơn, thanh toán khớp schema', function () {
    $get = fn (string $uri, string $path, array $headers = []): TestResponse => assertStorefrontContract($this->getJson($this->api.$uri, $headers)->assertSuccessful(), $path, 'GET');

    $get('/store', '/store');
    $get('/menus/header', '/menus/{location}');
    $get('/pages/home', '/pages/home');
    $get('/categories', '/categories');
    $get('/categories/dam-vay', '/categories/{slug}');
    $get('/brands', '/brands');
    $get("/brands/{$this->brand->slug}", '/brands/{slug}');
    $slug = $get('/products', '/products')->json('data.0.slug');
    $styleId = $get("/products/{$slug}", '/products/{slug}')->json('data.id');
    $get("/products?ids={$styleId}", '/products');
    $get('/search/suggestions?q=đầm', '/search/suggestions');
    $get('/address/provinces', '/address/provinces');
    $get('/address/provinces/29/wards', '/address/provinces/{province}/wards');
    assertStorefrontContract($this->post("{$this->api}/products/views", ['style_id' => $styleId], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent(), '/products/views', 'POST');

    $created = assertStorefrontContract($this->postJson("{$this->api}/carts")->assertCreated(), '/carts', 'POST');
    $cart = $created->json('data.id');
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $lineId = assertStorefrontContract($this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk(), '/carts/{cart}/lines', 'POST')->json('data.lines.0.id');
    $mLine = collect($this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->m->id, 'quantity' => 1], $headers)->assertOk()->json('data.lines'))->firstWhere('variant_id', $this->m->id)['id'];
    assertStorefrontContract($this->patchJson("{$this->api}/carts/{$cart}/lines/{$mLine}", ['quantity' => 2], $headers)->assertOk(), '/carts/{cart}/lines/{line}', 'PATCH');
    assertStorefrontContract($this->deleteJson("{$this->api}/carts/{$cart}/lines/{$mLine}", [], $headers)->assertOk(), '/carts/{cart}/lines/{line}', 'DELETE');
    assertStorefrontContract($this->putJson("{$this->api}/carts/{$cart}/selection", ['line_ids' => [$lineId]], $headers)->assertOk(), '/carts/{cart}/selection', 'PUT');
    assertStorefrontContract($this->putJson("{$this->api}/carts/{$cart}/vouchers", ['voucher_codes' => ['KHONG-CO']], $headers)->assertOk(), '/carts/{cart}/vouchers', 'PUT');
    $get("/carts/{$cart}", '/carts/{cart}', $headers);
    assertStorefrontContract($this->postJson("{$this->api}/checkout/{$cart}/quote", C::orderPayload(), $headers)->assertOk(), '/checkout/{cart}/quote', 'POST');

    $placed = assertStorefrontContract($this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'sf-contract-0001'])->assertCreated(), '/checkout/{cart}/orders', 'POST');
    $order = $placed->json('data.id');
    $orderHeaders = ['X-Vani-Order-Token' => $placed->json('data.access_token')];
    $get("/orders/{$order}", '/orders/{order}', $orderHeaders);
    $get("/orders/track?number={$placed->json('data.number')}&phone=0912345678", '/orders/track');
    $get("/payments/{$placed->json('data.payment.id')}", '/payments/{payment}');
    assertStorefrontContract($this->postJson("{$this->api}/orders/{$order}/cancel", ['reason' => 'Đổi ý'], $orderHeaders)->assertOk(), '/orders/{order}/cancel', 'POST');
});

it('khách đăng nhập: OTP, hồ sơ, mật khẩu, sổ địa chỉ, đồng ý nhận tin, đơn, giỏ, giá, xuất dữ liệu, xoá tài khoản', function () {
    assertStorefrontContract($this->postJson("{$this->api}/auth/otp/request", ['phone' => '0912345678'])->assertStatus(202), '/auth/otp/request', 'POST');
    $verify = $this->postJson("{$this->api}/auth/otp/verify", ['phone' => '0912345678', 'code' => FakeOtpSender::$codes['+84912345678|login']])->assertOk();
    assertStorefrontContract($verify, '/auth/otp/verify', 'POST');
    $auth = Cu::auth($verify->json('meta.token'));
    Cu::guestOrder($this, $this->s->id, 'sf-contract-me-01', ['phone' => '0912345678']);
    $call = fn (string $method, string $uri, string $path, array $body = []): TestResponse => assertStorefrontContract($this->json($method, $this->api.$uri, $body, $auth)->assertSuccessful(), $path, $method);

    $call('GET', '/me', '/me');
    $call('PATCH', '/me', '/me', ['full_name' => 'Lan']);
    $call('PUT', '/me/password', '/me/password', ['password' => 'mat-khau-moi-123', 'password_confirmation' => 'mat-khau-moi-123']);
    $address = $call('POST', '/me/addresses', '/me/addresses', [
        'full_name' => 'Lan', 'phone' => '0912345678', 'province_code' => '29', 'province_name' => 'Thành phố Hồ Chí Minh',
        'ward_code' => '70101065', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi',
    ])->json('data.id');
    $call('PATCH', "/me/addresses/{$address}", '/me/addresses/{address}', ['label' => 'Nhà']);
    $call('GET', '/me/addresses', '/me/addresses');
    $call('DELETE', "/me/addresses/{$address}", '/me/addresses/{address}');
    $call('GET', '/me/consents', '/me/consents');
    $call('PUT', '/me/consents', '/me/consents', ['channel' => 'email', 'purpose' => 'marketing', 'granted' => true]);
    $order = $call('GET', '/me/orders', '/me/orders')->json('data.0.id');
    $call('GET', "/me/orders/{$order}", '/me/orders/{order}');
    $call('GET', '/me/cart', '/me/cart');
    $call('GET', "/me/prices?variant_ids[]={$this->s->id}", '/me/prices');
    $call('GET', '/me/export', '/me/export');
    $call('GET', '/me/delete', '/me/delete');
    $call('POST', '/auth/logout', '/auth/logout');
});

it('phản hồi lỗi khớp error.v1 với đúng mã: 401, 404, 409, 422, 400 idempotency', function () {
    $created = $this->postJson("{$this->api}/carts")->assertCreated();
    $cart = $created->json('data.id');
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];

    $cases = [
        [$this->getJson("{$this->api}/me"), '/me', 'GET', 401, 'customer.unauthenticated'],
        [$this->getJson("{$this->api}/products/khong-co"), '/products/{slug}', 'GET', 404, null],
        [$this->postJson("{$this->api}/carts/{$cart}/lines", ['quantity' => 1], $headers), '/carts/{cart}/lines', 'POST', 422, 'validation.failed'],
    ];
    $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $cases[] = [$this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), $headers), '/checkout/{cart}/orders', 'POST', 400, null];
    $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'sf-contract-err-1'])->assertCreated();
    $cases[] = [$this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->m->id, 'quantity' => 1], $headers), '/carts/{cart}/lines', 'POST', 409, 'cart.closed'];

    foreach ($cases as [$response, $path, $method, $status, $code]) {
        $response->assertStatus($status);
        if ($code !== null) {
            $response->assertJsonPath('error.code', $code);
        }
        assertStorefrontContract($response, $path, $method);
    }
});
