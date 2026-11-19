<?php

use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\CurrentContext;

it('vai trò có quyền * được mọi thứ; nhiều vai trò cộng dồn quyền', function () {
    $owner = StaffUser::factory()->withPermissions(['*'])->create();
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->withPermissions(['orders.view'])->create();
    $authorizer = app(Authorizer::class);

    expect($authorizer->allows($owner->id, 'extension.plugins.manage'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'admin.access'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'orders.view'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'orders.manage'))->toBeFalse();
});

it('Gate dùng RBAC của Identity cho quyền đã đăng ký; quyền chưa đăng ký không được cấp qua vai trò', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access', 'quyen.chua-dang-ky'])->create();

    expect(Gate::forUser($staff)->allows('admin.access'))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('staff.manage'))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('quyen.chua-dang-ky'))->toBeFalse();
});

it('dashboard cần quyền admin.access', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['staff.manage'])->create(), 'staff')
        ->get('/admin')
        ->assertForbidden();

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff')
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard')->has('cards')->has('navigation'));
});

it('request Admin đặt actor là nhân viên đang đăng nhập', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin');

    expect(app(CurrentContext::class)->actor()->id)->toBe($staff->id);
});

it('nhân viên bị khoá khi đang đăng nhập sẽ bị đăng xuất', function () {
    $staff = StaffUser::factory()->inactive()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin')->assertRedirect(route('admin.login'));
});
