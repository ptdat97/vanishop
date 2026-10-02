<?php

use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\CurrentContext;

it('vai trò cấp địa điểm chỉ có quyền trên địa điểm đó', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'], ScopeType::Location, 5)->create();
    $authorizer = app(Authorizer::class);

    expect($authorizer->allows($staff->id, 'admin.access', ScopeRef::location(5)))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'admin.access', ScopeRef::location(6)))->toBeFalse()
        ->and($authorizer->allows($staff->id, 'admin.access'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'admin.access', ScopeRef::owner()))->toBeFalse();
});

it('vai trò cấp Owner với quyền * được mọi thứ, mọi địa điểm', function () {
    $staff = StaffUser::factory()->withPermissions(['*'])->create();
    $authorizer = app(Authorizer::class);

    expect($authorizer->allows($staff->id, 'extension.plugins.manage'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'staff.manage', ScopeRef::location(9)))->toBeTrue();
});

it('không có permission thì bị từ chối dù đúng phạm vi', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    expect(app(Authorizer::class)->allows($staff->id, 'staff.manage'))->toBeFalse();
});

it('Gate dùng RBAC theo phạm vi cho nhân viên', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'], ScopeType::Location, 5)->create();

    expect(Gate::forUser($staff)->allows('admin.access', [ScopeRef::location(5)]))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('admin.access', [ScopeRef::owner()]))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('admin.access'))->toBeTrue();
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
