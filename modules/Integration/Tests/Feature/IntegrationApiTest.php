<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Integration\Application\ClientProvisioning;
use Modules\Integration\Contracts\ExternalReferences;
use Modules\Integration\Domain\HmacSignature;
use Modules\Integration\Tests\Feature\IntegrationTestHelpers as H;
use Modules\Inventory\Persistence\Models\StockMovement;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/IntegrationTestHelpers.php';

beforeEach(function () {
    ['brand' => $this->brand, 'channel' => $this->channel, 's' => $this->s, 'm' => $this->m] = C::store();
    $this->erp = H::client('erp-main');
    $this->api = '/api/integration/v1';
    $this->placeOrder = function (string $key = 'integration-order-1'): string {
        $headers = ['X-Vani-Channel' => 'web-lumiere'];
        $created = $this->postJson('/api/storefront/v1/carts', [], $headers)->assertCreated();
        $headers['X-Vani-Cart-Token'] = $created->json('meta.token');
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => $key])
            ->assertCreated()->json('data.number');
    };
});

describe('xác thực', function () {
    it('thiếu/sai chữ ký, lệch giờ, key thu hồi, client tạm dừng → 401', function () {
        $this->getJson("{$this->api}/events")->assertStatus(401)->assertJsonPath('error.code', 'integration.unauthenticated');
        H::call($this, ['key' => $this->erp['key'], 'secret' => 'wrong'], 'GET', "{$this->api}/events")->assertStatus(401);
        H::call($this, $this->erp, 'GET', "{$this->api}/events", timestamp: now()->getTimestamp() - 301)->assertStatus(401);
        H::call($this, $this->erp, 'GET', "{$this->api}/events")->assertOk();

        // Chữ ký gắn với đường dẫn: dùng lại chữ ký của /events cho /orders bị từ chối.
        $signature = HmacSignature::header($this->erp['secret'], 'GET./api/integration/v1/events.', now()->getTimestamp());
        $this->getJson("{$this->api}/orders", ['X-Vani-Key-Id' => $this->erp['key'], 'X-Vani-Signature' => $signature])->assertStatus(401);

        $this->erp['client']->keys()->update(['revoked_at' => now()]);
        H::call($this, $this->erp, 'GET', "{$this->api}/events")->assertStatus(401);

        $pos = H::client('pos');
        $pos['client']->update(['status' => 'suspended']);
        H::call($this, $pos, 'GET', "{$this->api}/events")->assertStatus(401);
    });

    it('IP ngoài allowlist → 403; thiếu scope → 403', function () {
        $restricted = H::client('odo', ips: ['10.0.0.0/8']);
        H::call($this, $restricted, 'GET', "{$this->api}/events")->assertStatus(403)->assertJsonPath('error.code', 'integration.ip_not_allowed');

        $reader = H::client('pos', ['events:read']);
        H::call($this, $reader, 'GET', "{$this->api}/orders")->assertStatus(403)->assertJsonPath('error.code', 'integration.insufficient_scope');
        H::call($this, $reader, 'PUT', "{$this->api}/inventory/levels", ['levels' => []])->assertStatus(403);
    });

    it('xoay vòng key: tối đa 2 key còn hiệu lực, cả hai đều dùng được', function () {
        [$key2, $secret2] = T::seed(fn () => app(ClientProvisioning::class)->issueKey($this->erp['client']));
        H::call($this, ['key' => $key2, 'secret' => $secret2], 'GET', "{$this->api}/events")->assertOk();
        H::call($this, $this->erp, 'GET', "{$this->api}/events")->assertOk();

        T::seed(fn () => app(ClientProvisioning::class)->issueKey($this->erp['client']));
    })->throws(ValidationException::class);
});

it('event feed: phân trang theo cursor, lọc theo loại và data scope brand', function () {
    $other = Brand::factory()->create(['slug' => 'other', 'code' => 'OT']);
    H::publish('order.created', 'LU-1', $this->brand->id);
    H::publish('order.created', 'OT-1', $other->id);
    H::publish('order.confirmed', 'LU-1', $this->brand->id);

    $page = H::call($this, $this->erp, 'GET', "{$this->api}/events?limit=2")->assertOk();
    expect(array_column($page->json('data'), 'event_type'))->toBe(['order.created', 'order.created'])
        ->and($page->json('meta.has_more'))->toBeTrue();
    $next = H::call($this, $this->erp, 'GET', "{$this->api}/events?after={$page->json('meta.next_cursor')}")->assertOk();
    expect(array_column($next->json('data'), 'event_type'))->toBe(['order.confirmed'])
        ->and($next->json('meta.has_more'))->toBeFalse();

    $scoped = H::client('pos', ['events:read'], [$this->brand->id]);
    $data = H::call($this, $scoped, 'GET', "{$this->api}/events?type=order.created")->assertOk()->json('data');
    expect(array_column(array_column($data, 'aggregate'), 'id'))->toBe(['LU-1'])
        ->and($data[0]['event_id'])->not->toBeEmpty();
});

it('orders: danh sách theo updated_since + cursor, chi tiết canonical, không thấy đơn brand khác', function () {
    $first = ($this->placeOrder)('integration-order-1');
    $this->travel(5)->seconds();
    $second = ($this->placeOrder)('integration-order-2');

    $page = H::call($this, $this->erp, 'GET', "{$this->api}/orders?limit=1")->assertOk();
    expect(array_column($page->json('data'), 'number'))->toBe([$first])
        ->and($page->json('meta.next_cursor'))->not->toBeNull();
    $next = H::call($this, $this->erp, 'GET', "{$this->api}/orders?limit=1&cursor={$page->json('meta.next_cursor')}")->assertOk();
    expect(array_column($next->json('data'), 'number'))->toBe([$second]);

    $since = urlencode(T::seed(fn () => Order::query()->where('number', $second)->value('updated_at'))->toIso8601String());
    expect(array_column(H::call($this, $this->erp, 'GET', "{$this->api}/orders?updated_since={$since}")->json('data'), 'number'))->toBe([$second]);

    H::call($this, $this->erp, 'GET', "{$this->api}/orders/{$first}")->assertOk()
        ->assertJsonPath('data.schema', 'vanishop.order.v1')
        ->assertJsonPath('data.number', $first)
        ->assertJsonPath('data.status', 'processing')
        ->assertJsonPath('data.lines.0.sku', $this->s->sku);

    $other = Brand::factory()->create(['slug' => 'other', 'code' => 'OT']);
    $scoped = H::client('pos', ['orders:read'], [$other->id]);
    H::call($this, $scoped, 'GET', "{$this->api}/orders/{$first}")->assertStatus(404)->assertJsonPath('error.code', 'integration.order_not_found');
    expect(H::call($this, $scoped, 'GET', "{$this->api}/orders")->json('data'))->toBe([]);
});

it('acknowledgement: bắt buộc Idempotency-Key, idempotent, lưu external reference, chặn số chứng từ khác', function () {
    $number = ($this->placeOrder)();
    $uri = "{$this->api}/orders/{$number}/acknowledgements";

    H::call($this, $this->erp, 'POST', $uri, ['external_id' => 'SO-0001'])->assertStatus(400);
    H::call($this, $this->erp, 'POST', $uri, ['external_id' => 'SO-0001'], ['Idempotency-Key' => 'ack-key-0001'])->assertCreated()
        ->assertJsonPath('data.external_id', 'SO-0001');
    H::call($this, $this->erp, 'POST', $uri, ['external_id' => 'SO-0001'], ['Idempotency-Key' => 'ack-key-0001'])->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true');
    H::call($this, $this->erp, 'POST', $uri, ['external_id' => 'SO-9999'], ['Idempotency-Key' => 'ack-key-0002'])->assertStatus(409)
        ->assertJsonPath('error.code', 'integration.reference_conflict');
    H::call($this, $this->erp, 'POST', "{$this->api}/orders/XX-0000/acknowledgements", ['external_id' => 'SO-1'], ['Idempotency-Key' => 'ack-key-0003'])->assertStatus(404);

    expect(app(ExternalReferences::class)->externalId('erp-main', 'order', $number))->toBe('SO-0001')
        ->and(app(ExternalReferences::class)->internalId('erp-main', 'order', 'SO-0001'))->toBe($number);
});

describe('inventory levels', function () {
    beforeEach(function () {
        $this->erpLocation = I::location($this->brand, [$this->channel->id], ['code' => 'WH-ERP', 'stock_authority' => 'erp-main']);
        $this->onHand = fn (int $variantId): int => (int) DB::table('stock_levels')->where('location_id', $this->erpLocation->id)->where('variant_id', $variantId)->value('on_hand');
    });

    it('authority ghi on_hand tuyệt đối theo version; bản cũ → stale_update; SKU lạ báo riêng', function () {
        $uri = "{$this->api}/inventory/levels";
        H::call($this, $this->erp, 'PUT', $uri, ['levels' => [
            ['location_code' => 'WH-ERP', 'sku' => $this->s->sku, 'on_hand' => 42, 'version' => 10],
            ['location_code' => 'WH-ERP', 'sku' => 'KHONG-CO', 'on_hand' => 1, 'version' => 10],
        ]])->assertOk()
            ->assertJsonPath('data.results.0.status', 'applied')
            ->assertJsonPath('data.results.1.status', 'sku_not_found');

        H::call($this, $this->erp, 'PUT', $uri, ['levels' => [
            ['location_code' => 'WH-ERP', 'sku' => $this->s->sku, 'on_hand' => 5, 'version' => 9],
            ['location_code' => 'WH-ERP', 'sku' => $this->m->sku, 'on_hand' => 0, 'version' => 1],
        ]])->assertOk()
            ->assertJsonPath('data.results.0.status', 'stale_update')
            ->assertJsonPath('data.results.1.status', 'unchanged');

        expect(($this->onHand)($this->s->id))->toBe(42);
        $movement = StockMovement::query()->where('location_id', $this->erpLocation->id)->sole();
        expect($movement->type->value)->toBe('sync')
            ->and($movement->on_hand_delta)->toBe(42)
            ->and($movement->actor_type)->toBe('integration')
            ->and($movement->reference)->toBe('v10');
    });

    it('client không phải authority của location (hoặc location do VaniShop quản lý) → 403 not_data_owner, không ghi gì', function () {
        $pos = H::client('pos');
        H::call($this, $pos, 'PUT', "{$this->api}/inventory/levels", ['levels' => [
            ['location_code' => 'WH-ERP', 'sku' => $this->s->sku, 'on_hand' => 1, 'version' => 1],
        ]])->assertStatus(403)->assertJsonPath('error.code', 'not_data_owner');

        H::call($this, $this->erp, 'PUT', "{$this->api}/inventory/levels", ['levels' => [
            ['location_code' => 'WH-ERP', 'sku' => $this->s->sku, 'on_hand' => 7, 'version' => 1],
            ['location_code' => 'WH-HCM', 'sku' => $this->s->sku, 'on_hand' => 1, 'version' => 1],
        ]])->assertStatus(403)->assertJsonPath('error.details.location_code', 'WH-HCM');

        expect(($this->onHand)($this->s->id))->toBe(0);
    });
});
