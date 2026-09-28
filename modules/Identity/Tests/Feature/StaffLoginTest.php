<?php

use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;

it('hiển thị trang đăng nhập', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Identity::Auth/Login')->where('action', route('admin.login.store')));
});

it('chuyển khách chưa đăng nhập về trang đăng nhập Admin', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('đăng nhập thành công và ghi audit', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create(['password' => 'secret-password']);

    $this->post('/admin/login', ['email' => $staff->email, 'password' => 'secret-password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($staff, 'staff');
    expect($staff->refresh()->last_login_at)->not->toBeNull();
    expect(AuditLog::query()->where('action', 'identity.staff.login')->where('actor_id', $staff->id)->exists())->toBeTrue();
});

it('từ chối sai mật khẩu', function () {
    $staff = StaffUser::factory()->create(['password' => 'secret-password']);

    $this->post('/admin/login', ['email' => $staff->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest('staff');
});

it('từ chối nhân viên đã bị khoá', function () {
    $staff = StaffUser::factory()->inactive()->create(['password' => 'secret-password']);

    $this->post('/admin/login', ['email' => $staff->email, 'password' => 'secret-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest('staff');
});

it('khoá tạm sau 5 lần sai', function () {
    $this->freezeTime();
    $staff = StaffUser::factory()->create(['password' => 'secret-password']);
    RateLimiter::clear('staff-login:'.$staff->email.'|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->post('/admin/login', ['email' => $staff->email, 'password' => 'wrong']);
    }

    $this->post('/admin/login', ['email' => $staff->email, 'password' => 'secret-password'])
        ->assertSessionHasErrors(['email' => __('identity::auth.throttle', ['seconds' => 60])]);

    $this->assertGuest('staff');
});

it('validate dữ liệu đăng nhập', function (array $payload, string $field) {
    $this->post('/admin/login', $payload)->assertSessionHasErrors($field);
})->with([
    'thiếu email' => [['password' => 'x'], 'email'],
    'email sai định dạng' => [['email' => 'not-an-email', 'password' => 'x'], 'email'],
    'thiếu mật khẩu' => [['email' => 'a@example.com'], 'password'],
]);

it('đăng xuất', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->post('/admin/logout')->assertRedirect(route('admin.login'));

    $this->assertGuest('staff');
    expect(AuditLog::query()->where('action', 'identity.staff.logout')->exists())->toBeTrue();
});
