<?php

use Illuminate\Routing\Route;
use Illuminate\Testing\TestResponse;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Persistence\Models\Order;
use Tests\Support\JsonSchemaSubset;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Hợp đồng /api/integration/v1 (roadmap Phase 6): OpenAPI docs/api/openapi/integration-v1.json khớp route thật, và
| phản hồi thật (thành công + lỗi) khớp schema mà OpenAPI tham chiếu. Thêm/đổi endpoint mà không cập nhật OpenAPI → đỏ.
*/

function openApi(): array
{
    return json_decode((string) file_get_contents(base_path('docs/api/openapi/integration-v1.json')), true, flags: JSON_THROW_ON_ERROR);
}

/** Schema phản hồi (file JSON) mà OpenAPI khai báo cho path + method + status. */
function responseSchema(string $path, string $method, int $status): string
{
    $spec = openApi();
    $response = $spec['paths'][$path][strtolower($method)]['responses'][(string) $status] ?? null;
    expect($response)->not->toBeNull("OpenAPI thiếu {$method} {$path} → {$status}");
    if (isset($response['$ref'])) {
        $response = $spec['components']['responses'][basename($response['$ref'])];
    }

    return base_path('docs/api/openapi/'.$response['content']['application/json']['schema']['$ref']);
}

function assertMatchesContract(TestResponse $response, string $path, string $method): void
{
    $errors = JsonSchemaSubset::validate($response->json(), responseSchema($path, $method, $response->status()));
    expect($errors)->toBe([], "{$method} {$path} {$response->status()}: ".implode('; ', $errors));
}

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->erp = H::client('erp-main');
    $this->api = '/api/integration/v1';
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'contract-order-0001'])->assertCreated();
    $this->number = Order::query()->withoutGlobalScopes()->value('number');
});

it('mọi route /api/integration/v1 có trong OpenAPI (đúng method) và ngược lại', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), 'api.integration.v1.'))
        ->flatMap(fn (Route $route) => collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')
            ->map(fn (string $method): string => $method.' '.preg_replace('#^api/integration/v1#', '', $route->uri())))
        ->sort()->values()->all();
    $documented = collect(openApi()['paths'])->flatMap(fn (array $operations, string $path) => collect(array_keys($operations))
        ->map(fn (string $method): string => strtoupper($method).' '.$path))->sort()->values()->all();

    expect($documented)->toBe($routes);
});

it('phản hồi thành công khớp schema: events, orders, order, acknowledgement (+ replay), inventory levels', function () {
    assertMatchesContract(H::call($this, $this->erp, 'GET', "{$this->api}/events?limit=2")->assertOk(), '/events', 'GET');
    assertMatchesContract(H::call($this, $this->erp, 'GET', "{$this->api}/orders")->assertOk(), '/orders', 'GET');
    assertMatchesContract(H::call($this, $this->erp, 'GET', "{$this->api}/orders/{$this->number}")->assertOk(), '/orders/{number}', 'GET');

    $ack = fn () => H::call($this, $this->erp, 'POST', "{$this->api}/orders/{$this->number}/acknowledgements", ['external_id' => 'SO-001'], ['Idempotency-Key' => 'ack-contract-0001']);
    assertMatchesContract($ack()->assertCreated(), '/orders/{number}/acknowledgements', 'POST');
    $ack()->assertCreated()->assertHeader('Idempotent-Replayed', 'true');

    I::location(['code' => 'WH-ERP', 'stock_authority' => 'erp-main']);
    $levels = H::call($this, $this->erp, 'PUT', "{$this->api}/inventory/levels", ['levels' => [
        ['location_code' => 'WH-ERP', 'sku' => $this->s->sku, 'on_hand' => 5, 'version' => 1],
        ['location_code' => 'WH-ERP', 'sku' => 'NO-SUCH-SKU', 'on_hand' => 1, 'version' => 1],
    ]])->assertOk();
    assertMatchesContract($levels, '/inventory/levels', 'PUT');
});

it('phản hồi lỗi khớp error.v1 với đúng mã: 401, 403, 404, 400 idempotency, 409, 422', function () {
    $cases = [
        [$this->call('GET', "{$this->api}/events", server: ['HTTP_ACCEPT' => 'application/json']), '/events', 'GET', 401, 'integration.unauthenticated'],
        [H::call($this, H::client('pos', ['events:read']), 'GET', "{$this->api}/orders"), '/orders', 'GET', 403, 'integration.insufficient_scope'],
        [H::call($this, $this->erp, 'GET', "{$this->api}/orders/KHONG-CO"), '/orders/{number}', 'GET', 404, 'integration.order_not_found'],
        [H::call($this, $this->erp, 'POST', "{$this->api}/orders/{$this->number}/acknowledgements", ['external_id' => 'SO-1']), '/orders/{number}/acknowledgements', 'POST', 400, 'integration.idempotency_key_required'],
        [H::call($this, $this->erp, 'GET', "{$this->api}/events?limit=9999"), '/events', 'GET', 422, 'validation.failed'],
    ];
    H::call($this, $this->erp, 'POST', "{$this->api}/orders/{$this->number}/acknowledgements", ['external_id' => 'SO-A'], ['Idempotency-Key' => 'ack-contract-a001'])->assertCreated();
    $cases[] = [H::call($this, $this->erp, 'POST', "{$this->api}/orders/{$this->number}/acknowledgements", ['external_id' => 'SO-B'], ['Idempotency-Key' => 'ack-contract-b001']), '/orders/{number}/acknowledgements', 'POST', 409, 'integration.reference_conflict'];

    foreach ($cases as [$response, $path, $method, $status, $code]) {
        $response->assertStatus($status)->assertJsonPath('error.code', $code);
        assertMatchesContract($response, $path, $method);
    }
});
