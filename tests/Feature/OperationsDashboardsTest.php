<?php

use Modules\Identity\Persistence\Models\StaffUser;

it('Horizon và Pulse nằm dưới đường dẫn Admin, cần đăng nhập nhân viên và quyền system.monitor', function (string $path) {
    $this->get($path)->assertRedirect(route('admin.login'));

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff')->get($path)->assertForbidden();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'system.monitor'])->create(), 'staff')->get($path)->assertOk();
})->with(['horizon' => '/admin/system/horizon', 'pulse' => '/admin/system/pulse']);

it('không còn đường dẫn mặc định /horizon, /pulse', function () {
    $this->get('/horizon')->assertNotFound();
    $this->get('/pulse')->assertNotFound();
});
