<?php

use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Authorizer;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Identity\Domain\ScopeType;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Persistence\Models\LegalEntity;

it('vai trò cấp brand chỉ có quyền trên brand đó', function () {
    [$lumiere, $urbanx] = Brand::factory()->count(2)->create();
    $staff = StaffUser::factory()->withPermissions(['admin.access'], ScopeType::Brand, $lumiere->id)->create();
    $authorizer = app(Authorizer::class);

    expect($authorizer->allows($staff->id, 'admin.access', ScopeRef::brand($lumiere->id)))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'admin.access', ScopeRef::brand($urbanx->id)))->toBeFalse()
        ->and($authorizer->allows($staff->id, 'admin.access'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'admin.access', ScopeRef::owner()))->toBeFalse()
        ->and($authorizer->accessibleBrandIds($staff->id))->toBe([$lumiere->id]);
});

it('vai trò cấp pháp nhân bao phủ các brand của pháp nhân đó', function () {
    $company = LegalEntity::factory()->create();
    $own = Brand::factory()->count(2)->create(['legal_entity_id' => $company->id]);
    $other = Brand::factory()->create();
    $staff = StaffUser::factory()->withPermissions(['staff.manage'], ScopeType::LegalEntity, $company->id)->create();
    $authorizer = app(Authorizer::class);

    expect($authorizer->allows($staff->id, 'staff.manage', ScopeRef::brand($own[0]->id)))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'staff.manage', ScopeRef::brand($other->id)))->toBeFalse()
        ->and($authorizer->allows($staff->id, 'staff.manage', ScopeRef::legalEntity($company->id)))->toBeTrue()
        ->and($authorizer->accessibleBrandIds($staff->id))->toBe($own->pluck('id')->sort()->values()->all());
});

it('vai trò cấp Owner với quyền * được mọi thứ và thấy mọi brand', function () {
    $brand = Brand::factory()->create();
    $staff = StaffUser::factory()->withPermissions(['*'])->create();
    $authorizer = app(Authorizer::class);

    expect($authorizer->allows($staff->id, 'extension.plugins.manage'))->toBeTrue()
        ->and($authorizer->allows($staff->id, 'staff.manage', ScopeRef::brand($brand->id)))->toBeTrue()
        ->and($authorizer->accessibleBrandIds($staff->id))->toBeNull();
});

it('không có permission thì bị từ chối dù đúng phạm vi', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    expect(app(Authorizer::class)->allows($staff->id, 'staff.manage'))->toBeFalse();
});

it('Gate dùng RBAC theo phạm vi cho nhân viên', function () {
    $brand = Brand::factory()->create();
    $staff = StaffUser::factory()->withPermissions(['admin.access'], ScopeType::Brand, $brand->id)->create();

    expect(Gate::forUser($staff)->allows('admin.access', [ScopeRef::brand($brand->id)]))->toBeTrue()
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

it('request Admin đặt phạm vi brand theo phân quyền của nhân viên', function () {
    $brand = Brand::factory()->create();
    $staff = StaffUser::factory()->withPermissions(['admin.access'], ScopeType::Brand, $brand->id)->create();

    $this->actingAs($staff, 'staff')->get('/admin');

    expect(app(CurrentContext::class)->brandIds())->toBe([$brand->id]);
});

it('nhân viên bị khoá khi đang đăng nhập sẽ bị đăng xuất', function () {
    $staff = StaffUser::factory()->inactive()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin')->assertRedirect(route('admin.login'));
});
