<?php

use Illuminate\Support\Env;
use Modules\Identity\Application\RecordStaffLogin;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;

/**
 * Đặt biến môi trường cho lần boot kế tiếp. Repository env của Laravel là static và nhớ các biến nó đã nạp
 * từ .env (sẽ ghi đè lại khi refreshApplication), nên phải reset để giá trị của test được coi là "external".
 */
function setAdminPathEnv(?string $value): void
{
    (new ReflectionProperty(Env::class, 'repository'))->setValue(null, null);

    if ($value === null) {
        putenv('VANI_ADMIN_PATH');
        unset($_ENV['VANI_ADMIN_PATH'], $_SERVER['VANI_ADMIN_PATH']);

        return;
    }

    putenv("VANI_ADMIN_PATH={$value}");
    $_ENV['VANI_ADMIN_PATH'] = $_SERVER['VANI_ADMIN_PATH'] = $value;
}

afterEach(fn () => setAdminPathEnv(null));

it('đổi đường dẫn Admin bằng VANI_ADMIN_PATH', function () {
    setAdminPathEnv('quan-tri-7f3k');
    $this->refreshApplication();

    $this->get('/quan-tri-7f3k/login')->assertOk();
    $this->get('/admin/login')->assertNotFound();
    $this->get('/quan-tri-7f3k')->assertRedirect('/quan-tri-7f3k/login');
    expect(route('admin.plugins.index', absolute: false))->toBe('/quan-tri-7f3k/plugins');
});

it('từ chối đường dẫn Admin trùng đường dẫn dành riêng', function () {
    setAdminPathEnv('api');

    expect(fn () => $this->refreshApplication())->toThrow(InvalidArgumentException::class, 'dành riêng');
});

it('gắn header noindex cho trang Admin', function () {
    $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('/')->assertHeaderMissing('X-Robots-Tag');
});

it('chặn Admin theo IP allowlist bằng 404', function () {
    config(['vanishop.admin.ip_allowlist' => ['10.0.0.0/8', '203.0.113.7']]);

    $this->get('/admin/login')->assertNotFound();
    $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])->get('/admin/login')->assertOk();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->get('/admin/login')->assertOk();
});

it('Admin dùng cookie phiên riêng giới hạn theo đường dẫn Admin', function () {
    config(['session.driver' => 'file']);

    $admin = $this->get('/admin/login');
    $adminSession = collect($admin->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());

    expect($adminSession)->toHaveKey('vanishop_admin_session')
        ->and($adminSession['vanishop_admin_session']->getPath())->toBe('/admin')
        ->and($adminSession['XSRF-TOKEN']->getPath())->toBe('/admin');

    $storefront = $this->get('/');
    $storefrontCookies = collect($storefront->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());

    expect($storefrontCookies)->not->toHaveKey('vanishop_admin_session')
        ->and($storefrontCookies[config('session.cookie')]->getPath())->toBe('/');
});

it('ghi cảnh báo khi nhân viên đăng nhập từ IP mới', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create(['password' => 'a-very-long-password']);
    $login = fn (string $ip) => $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post('/admin/login', ['email' => $staff->email, 'password' => 'a-very-long-password']);

    $login('10.0.0.1');
    $this->post('/admin/logout');
    $login('10.0.0.1');
    $this->post('/admin/logout');

    expect(AuditLog::query()->where('action', RecordStaffLogin::ACTION_NEW_IP)->count())->toBe(0);

    $login('10.0.0.99');

    $alert = AuditLog::query()->where('action', RecordStaffLogin::ACTION_NEW_IP)->sole();
    expect($alert->actor_id)->toBe($staff->id)
        ->and($alert->changes)->toBe(['ip' => '10.0.0.99']);
});
