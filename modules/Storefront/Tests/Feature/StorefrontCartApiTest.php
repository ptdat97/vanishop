<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->channel = Channel::factory()->forBrand($this->brand, 'vani.test', '/lumiere')->create(['code' => 'web-lumiere']);
    $this->style = T::product($this->brand->id, ['name' => 'Đầm lụa', 'slug' => 'dam-lua']);
    [$this->s, $this->m] = P::variants($this->style);
    P::priceList($this->brand->id, ['code' => 'base'], [$this->channel->id], [$this->s->id => [590_000], $this->m->id => [590_000, 690_000]]);
    $warehouse = I::location($this->brand, [$this->channel->id]);
    I::stock($warehouse, $this->s->id, 10);
    I::stock($warehouse, $this->m->id, 1);
    $this->headers = ['X-Vani-Channel' => 'web-lumiere'];
});

function newCart($test): array
{
    $response = $test->postJson('/api/storefront/v1/carts', [], $test->headers)->assertCreated();

    return [$response->json('data.id'), [...$test->headers, 'X-Vani-Cart-Token' => $response->json('meta.token')]];
}

it('tạo giỏ, thêm/sửa/xoá dòng qua API', function () {
    [$id, $headers] = newCart($this);
    expect($id)->toHaveLength(26);

    $this->postJson("/api/storefront/v1/carts/{$id}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)
        ->assertOk()
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonPath('data.subtotal.amount', 1_180_000)
        ->assertJsonPath('data.subtotal.formatted', '1.180.000 ₫')
        ->assertJsonPath('data.checkout_ready', true)
        ->assertJsonPath('data.lines.0.product.name', 'Đầm lụa')
        ->assertJsonPath('data.lines.0.issues', []);

    $response = $this->postJson("/api/storefront/v1/carts/{$id}/lines", ['variant_id' => $this->m->id, 'quantity' => 1], $headers)
        ->assertJsonPath('data.lines.1.compare_at.amount', 690_000);
    $lineId = $response->json('data.lines.0.id');

    $this->patchJson("/api/storefront/v1/carts/{$id}/lines/{$lineId}", ['quantity' => 1], $headers)->assertJsonPath('data.lines.0.quantity', 1);
    $this->deleteJson("/api/storefront/v1/carts/{$id}/lines/{$lineId}", [], $headers)->assertJsonCount(1, 'data.lines');
    $this->getJson("/api/storefront/v1/carts/{$id}", $headers)->assertOk()->assertJsonPath('data.item_count', 1);
});

it('lỗi nghiệp vụ có mã ổn định, không lộ số tồn', function () {
    [$id, $headers] = newCart($this);

    $response = $this->postJson("/api/storefront/v1/carts/{$id}/lines", ['variant_id' => $this->m->id, 'quantity' => 2], $headers)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'cart.insufficient_stock')
        ->assertJsonPath('error.details.variant_id', $this->m->id);
    expect($response->json('error.details'))->not->toHaveKey('available');

    $this->postJson("/api/storefront/v1/carts/{$id}/lines", ['variant_id' => 999999, 'quantity' => 1], $headers)
        ->assertStatus(422)->assertJsonPath('error.code', 'cart.variant_unavailable');
    $this->postJson("/api/storefront/v1/carts/{$id}/lines", ['variant_id' => $this->s->id, 'quantity' => 0], $headers)
        ->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});

it('thiếu hoặc sai token thì như giỏ không tồn tại', function () {
    [$id] = newCart($this);

    $this->getJson("/api/storefront/v1/carts/{$id}", $this->headers)->assertNotFound()->assertJsonPath('error.code', 'cart.not_found');
    $this->getJson("/api/storefront/v1/carts/{$id}", [...$this->headers, 'X-Vani-Cart-Token' => 'x'])->assertNotFound();
    $this->getJson('/api/storefront/v1/carts/01JABCDEFGHJKMNPQRSTVWXYZ0', [...$this->headers, 'X-Vani-Cart-Token' => 'x'])->assertNotFound();
});

it('giới hạn tần suất tạo giỏ theo IP', function () {
    foreach (range(1, 30) as $ignored) {
        $this->postJson('/api/storefront/v1/carts', [], $this->headers)->assertCreated();
    }

    $this->postJson('/api/storefront/v1/carts', [], $this->headers)->assertStatus(429);
});
