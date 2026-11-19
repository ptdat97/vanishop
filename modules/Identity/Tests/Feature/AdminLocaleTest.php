<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;

it('ADR-049: trang đăng nhập đổi ngôn ngữ theo phiên; bản dịch Vue nhóm *::admin theo ngôn ngữ đang chọn', function () {
    $this->get('/admin/login')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('app.locale', 'vi')
        ->where('app.localeUrl', route('admin.locale.update'))
        ->where('adminI18n.shared::admin.layout.sign_out', 'Đăng xuất'));

    $this->from('/admin/login')->put('/admin/ngon-ngu', ['locale' => 'en'])->assertRedirect('/admin/login');

    $page = $this->get('/admin/login')->assertOk()->viewData('page');
    expect($page['props']['app']['locale'])->toBe('en')
        ->and($page['props']['adminI18n']['shared::admin']['layout']['sign_out'])->toBe('Sign out')
        ->and(array_keys($page['onceProps']))->toContain('admin-i18n.en.'.config('vanishop.version'));

    $this->put('/admin/ngon-ngu', ['locale' => 'xx'])->assertSessionHasErrors('locale');
});

it('ADR-049: nhân viên chọn ngôn ngữ → lưu vào hồ sơ (audit), áp cho chuỗi server và props; hồ sơ thắng phiên', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create(['locale' => null]);
    $this->actingAs($staff, 'staff');

    $this->from('/admin')->put('/admin/ngon-ngu', ['locale' => 'en'])->assertRedirect('/admin');
    expect($staff->fresh()->locale)->toBe('en')
        ->and(AuditLog::query()->where('action', 'identity.staff.profile_updated')->where('subject_id', $staff->id)->exists())->toBeTrue();

    $this->get('/admin/ho-so')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('app.locale', 'en')
        ->where('adminI18n.shared::admin.layout.view_store', 'View store'));

    $staff->update(['locale' => 'vi']);
    $this->withSession(['admin_locale' => 'en'])->get('/admin/ho-so')->assertInertia(fn (Assert $page) => $page->where('app.locale', 'vi'));
});
