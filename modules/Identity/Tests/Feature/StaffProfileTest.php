<?php

use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;

beforeEach(function () {
    $this->staff = StaffUser::factory()->withPermissions(['admin.access'])->create(['name' => 'Lan', 'password' => 'mat-khau-cu-123']);
    $this->actingAs($this->staff, 'staff');
});

it('mọi nhân viên mở được hồ sơ của mình; đổi tên và ngôn ngữ Admin riêng (thông báo theo ngôn ngữ đó)', function () {
    $this->get('/admin/ho-so')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Identity::Profile')
        ->where('profile.email', $this->staff->email)->where('profile.locale', null)
        ->where('urls.profile', route('admin.profile.edit')));

    $this->put('/admin/ho-so', ['name' => ' Lan Nguyễn ', 'locale' => 'en'])->assertSessionHas('success', 'Profile saved.');
    expect($this->staff->fresh())->name->toBe('Lan Nguyễn')->locale->toBe('en');

    $this->get('/admin/ho-so')->assertInertia(fn (Assert $page) => $page->where('app.locale', 'en'));
    $this->put('/admin/ho-so', ['name' => '', 'locale' => 'xx'])->assertSessionHasErrors(['name', 'locale']);
    expect(AuditLog::query()->where('action', 'identity.staff.profile_updated')->count())->toBe(1);
});

it('đổi mật khẩu cần mật khẩu hiện tại đúng và theo chính sách mật khẩu', function () {
    $this->put('/admin/ho-so/mat-khau', ['current_password' => 'sai', 'password' => 'Mat-khau-moi-456!', 'password_confirmation' => 'Mat-khau-moi-456!'])
        ->assertSessionHasErrors('current_password');
    $this->put('/admin/ho-so/mat-khau', ['current_password' => 'mat-khau-cu-123', 'password' => 'x', 'password_confirmation' => 'x'])
        ->assertSessionHasErrors('password');

    $this->put('/admin/ho-so/mat-khau', ['current_password' => 'mat-khau-cu-123', 'password' => 'Mat-khau-moi-456!', 'password_confirmation' => 'Mat-khau-moi-456!'])
        ->assertSessionHasNoErrors();
    expect(Hash::check('Mat-khau-moi-456!', $this->staff->fresh()->password))->toBeTrue()
        ->and(AuditLog::query()->where('action', 'identity.staff.password_changed')->exists())->toBeTrue();
});

it('vani:staff:reset-password in link đặt lại (đang hoạt động) hoặc lời mời (chưa kích hoạt); từ chối nhân viên bị khoá, email lạ', function () {
    $this->artisan('vani:staff:reset-password', ['email' => $this->staff->email])
        ->expectsOutputToContain(url('/admin/dat-lai-mat-khau/'))->assertSuccessful();

    $invited = StaffUser::factory()->create(['status' => StaffUser::STATUS_INVITED]);
    $this->artisan('vani:staff:reset-password', ['email' => $invited->email])->expectsOutputToContain(url('/admin/loi-moi/'))->assertSuccessful();

    $locked = StaffUser::factory()->create(['status' => StaffUser::STATUS_LOCKED]);
    $this->artisan('vani:staff:reset-password', ['email' => $locked->email])->assertFailed();
    $this->artisan('vani:staff:reset-password', ['email' => 'khong-co@shop.test'])->assertFailed();

    expect(AuditLog::query()->where('action', 'identity.staff.password_link_issued')->count())->toBe(2);
});
