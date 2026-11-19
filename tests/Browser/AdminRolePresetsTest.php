<?php

use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\Role;
use Modules\Identity\Persistence\Models\StaffUser;

/*
| Admin → Vai trò → Thêm: bấm một vai trò mẫu thì điền tên, mã và tích đúng các quyền của mẫu; chỉnh rồi lưu được.
*/

it('bấm mẫu "Phân tích dữ liệu": điền tên/mã, tích quyền xem và xuất, không tích quyền xem liên hệ; lưu thành vai trò', function () {
    $owner = Role::query()->firstOrCreate(['code' => 'owner_admin'], ['name' => 'Owner Admin', 'is_system' => true]);
    $owner->syncPermissions([PermissionRegistry::WILDCARD]);
    $staff = StaffUser::factory()->create();
    $staff->roleAssignments()->create(['role_id' => $owner->id, 'scope_type' => ScopeType::Owner, 'scope_id' => null]);
    $this->actingAs($staff, 'staff');

    $page = visit('/admin/staff/roles/create')->assertSee('Bắt đầu từ vai trò mẫu')->click('[data-role-preset="analyst"]');
    $checked = fn (string $code): bool => (bool) $page->script("document.querySelector('input[type=checkbox][value=\"{$code}\"]')?.checked");

    expect($page->script("document.querySelector('input.w-full, form input')?.value"))->toBe('Phân tích dữ liệu')
        ->and($checked('orders.export'))->toBeTrue()
        ->and($checked('orders.view'))->toBeTrue()
        ->and($checked('customers.pii'))->toBeFalse()
        ->and($checked('orders.manage'))->toBeFalse();

    $page->click('form button[type=submit]')->assertSee('Phân tích dữ liệu')->assertNoJavaScriptErrors();
    expect(Role::query()->where('code', 'analyst')->exists())->toBeTrue();
});
