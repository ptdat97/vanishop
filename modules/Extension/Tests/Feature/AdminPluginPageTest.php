<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\StaffUser;

it('liệt kê plugin tìm thấy trong custom/plugin', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access', 'extension.plugins.view'])->create();

    $this->actingAs($staff, 'staff')
        ->get('/admin/plugins')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Extension::Plugins/Index')
            ->where('plugins', fn (iterable $plugins): bool => collect($plugins)
                ->contains('id', 'vani.hello-world')
                && collect($plugins)->every(fn (array $plugin): bool => $plugin['status'] === ($plugin['bundled'] ? 'enabled' : 'discovered'))
                && collect($plugins)->where('bundled', true)->pluck('id')->sort()->values()->all() === ['vani.bank-transfer', 'vani.cod', 'vani.shipping-flat-rate', 'vani.tax-vn-vat']));
});

it('cần quyền extension.plugins.view', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin/plugins')->assertForbidden();
});
