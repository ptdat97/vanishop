<?php

use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;

require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';

/*
| Menu Admin "Giao hàng" (ADR-043, SME trước): shop nhỏ làm việc giao hàng trên trang đơn — trang vận đơn riêng chỉ lên menu
| khi có hãng tự đặt vận đơn qua API hoặc từ 2 kho giao online; `VANI_FULFILLMENT_MENU` ép always/never. Trang vẫn mở được.
*/

beforeEach(function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'orders.view', 'fulfillment.view'])->create(), 'staff');
    $this->menu = fn (): bool => collect($this->get('/admin')->viewData('page')['props']['navigation'])->pluck('key')->contains('fulfillment');
});

it('mặc định (hãng nhập tay, một kho): không có trên menu nhưng trang vẫn mở được', function () {
    I::location(['code' => 'MAIN']);

    expect(($this->menu)())->toBeFalse();
    $this->get('/admin/fulfillment/shipments')->assertOk();
});

it('hiện khi có từ 2 kho giao online (vani.warehouse) — kho không giao online hoặc ngừng hoạt động không tính', function () {
    I::location(['code' => 'MAIN']);
    I::location(['code' => 'SHOP', 'ships_online_orders' => false]);
    I::location(['code' => 'OLD', 'status' => 'inactive']);
    expect(($this->menu)())->toBeFalse();

    I::location(['code' => 'WH2']);
    expect(($this->menu)())->toBeTrue();
});

it('hiện khi có hãng tự đặt vận đơn qua API; cấu hình always/never ghi đè', function () {
    app(Extensions::class)->tag([FakeApiCarrier::class], ShippingCarrier::CARRIERS_TAG);
    expect(($this->menu)())->toBeTrue();

    config(['vanishop.fulfillment.admin_menu' => 'never']);
    expect(($this->menu)())->toBeFalse();
    config(['vanishop.fulfillment.admin_menu' => 'always']);
    expect(($this->menu)())->toBeTrue();
});
