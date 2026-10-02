<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\ProvincesVn\Infrastructure\DivisionsHealthCheck;
use Plugin\ProvincesVn\Infrastructure\VnDivisions;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->place = function (array $address, string $key = 'vn-order-0001') {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000, 'shipping_address' => $address]), [...$headers, 'Idempotency-Key' => $key]);
    };
});

it('dữ liệu: 34 tỉnh/thành, 3.321 phường/xã, mã không trùng; health ok', function () {
    $divisions = app(VnDivisions::class);
    $wards = array_merge(...array_map(fn (array $province): array => $divisions->wards($province['code']), $divisions->provinces()));

    expect($divisions->provinces())->toHaveCount(34)
        ->and($wards)->toHaveCount(3321)
        ->and(array_unique(array_column($wards, 'code')))->toHaveCount(3321)
        ->and($divisions->ward('29', '70101065'))->toBe(['code' => '70101065', 'name' => 'Phường Bến Thành'])
        ->and($divisions->ward('01', '70101065'))->toBeNull()
        ->and(app(DivisionsHealthCheck::class)->check()->status)->toBe('ok');
});

it('Storefront API: danh sách tỉnh và phường/xã theo tỉnh; tỉnh không có → 404', function () {
    $this->getJson('/api/storefront/v1/address/provinces')->assertOk()
        ->assertJsonCount(34, 'data')->assertJsonPath('meta.directory', 'vn-2025')->assertJsonPath('data.0.code', '01');
    $this->getJson('/api/storefront/v1/address/provinces/29/wards')->assertOk()->assertJsonFragment(['code' => '70101065', 'name' => 'Phường Bến Thành']);
    $this->getJson('/api/storefront/v1/address/provinces/99/wards')->assertNotFound();
});

it('đặt hàng: mã phường phải thuộc đúng tỉnh; tên lưu vào đơn lấy theo danh mục', function () {
    ($this->place)(['province_code' => '01', 'ward_code' => '70101065', 'street_line' => '1 Lê Lợi'])
        ->assertStatus(422)->assertJsonPath('error.details.issues.0.code', 'address_invalid');

    ($this->place)(['province_code' => '29', 'ward_code' => '70101065', 'province_name' => 'hcm', 'ward_name' => 'bt', 'street_line' => '12 Lê Lợi'], 'vn-order-0002')->assertCreated();
    expect(Order::query()->withoutGlobalScopes()->sole()->shipping_address)->toMatchArray(['province_name' => 'Thành phố Hồ Chí Minh', 'ward_name' => 'Phường Bến Thành', 'ward_code' => '70101065']);
});

it('checkout native: chọn tỉnh/phường; tắt plugin → nhập tự do', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->get('/thanh-toan')->assertSee('name="shipping_address[province_code]"', false)->assertSee('Thành phố Hồ Chí Minh');
    $this->withSession(['_old_input' => ['shipping_address' => ['province_code' => '29']]])->get('/thanh-toan')->assertSee('Phường Bến Thành');

    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PluginManager::class)->disable('vani.provinces-vn'));
    app(PluginActivation::class)->flush();
    $this->get('/thanh-toan')->assertSee('name="shipping_address[province_name]"', false)->assertDontSee('name="shipping_address[province_code]"', false);
    $this->getJson('/api/storefront/v1/address/provinces')->assertOk()->assertJsonPath('data', [])->assertJsonPath('meta.directory', null);
});
