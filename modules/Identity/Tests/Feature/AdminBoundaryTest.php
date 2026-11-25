<?php

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Application\AdminPathSetting;
use Modules\Identity\Application\RecordStaffLogin;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Tenancy\Contracts\Settings;

/**
 * Đặt biến môi trường cho lần boot kế tiếp. Repository env của Laravel là static và nhớ các biến nó đã nạp
 * từ .env (sẽ ghi đè lại khi refreshApplication), nên phải reset để giá trị của test được coi là "external".
 */
function setBootEnv(string $name, ?string $value): void
{
    (new ReflectionProperty(Env::class, 'repository'))->setValue(null, null);

    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);

        return;
    }

    putenv("{$name}={$value}");
    $_ENV[$name] = $_SERVER[$name] = $value;
}

afterEach(function () {
    Cache::store('file')->forget('vani:admin-path');
    setBootEnv('CACHE_STORE', null);
});

it('khởi động dùng đường dẫn Admin đã lưu: route Admin, Horizon, Pulse dưới đường dẫn mới', function () {
    // SQLite in-memory không sống qua refreshApplication → đặt sẵn giá trị đã lưu vào cache (đường đọc lúc khởi động).
    setBootEnv('CACHE_STORE', 'file');
    Cache::store('file')->forever('vani:admin-path', 'quan-tri-7f3k');
    $this->refreshApplication();
    $this->withoutVite(); // app mới: tắt Vite lại như TestCase::setUp (không phụ thuộc public/build)

    $this->get('/quan-tri-7f3k/login')->assertOk();
    $this->get('/admin/login')->assertNotFound();
    $this->get('/quan-tri-7f3k')->assertRedirect('/quan-tri-7f3k/login');
    expect(route('admin.plugins.index', absolute: false))->toBe('/quan-tri-7f3k/plugins')
        ->and(config('horizon.path'))->toBe('quan-tri-7f3k/system/horizon')
        ->and(config('pulse.path'))->toBe('quan-tri-7f3k/system/pulse');
});

it('giá trị đã lưu không hợp lệ hoặc trùng đường dẫn dành riêng → dùng mặc định "admin"', function () {
    setBootEnv('CACHE_STORE', 'file');
    Cache::store('file')->forever('vani:admin-path', 'api');
    $this->refreshApplication();

    expect(config('vanishop.admin.path'))->toBe('admin');
});

it('chưa cấu hình → "admin"; chỉ super admin đổi được, đổi xong chuyển tới trang đăng nhập mới; từ chối đường dẫn sai hoặc đang dùng', function () {
    expect(app(AdminPathSetting::class)->current())->toBe('admin');
    $manager = StaffUser::factory()->withPermissions(['admin.access', 'staff.manage', 'settings.manage'])->create();
    $this->actingAs($manager, 'staff')->get('/admin/security/admin-path')->assertForbidden();
    $this->put('/admin/security/admin-path', ['path' => 'quan-tri'])->assertForbidden();

    $owner = StaffUser::factory()->withPermissions(['*'])->create();
    $this->actingAs($owner, 'staff')->get('/admin/security/admin-path')
        ->assertInertia(fn (Assert $page) => $page->component('Identity::Security/AdminPath')->where('path', 'admin')->where('defaultPath', 'admin'));
    foreach (['Quan Tri', 'api', 'tai-khoan', 'en', 'san-pham'] as $bad) {
        $this->put('/admin/security/admin-path', ['path' => $bad])->assertSessionHasErrors('path');
    }

    $this->put('/admin/security/admin-path', ['path' => 'quan-tri-7f3k'], ['X-Inertia' => 'true'])
        ->assertStatus(409)->assertHeader('X-Inertia-Location', url('/quan-tri-7f3k/login'));
    expect(app(Settings::class)->get('core', AdminPathSetting::SETTING))->toBe('quan-tri-7f3k')
        ->and(app(AdminPathSetting::class)->current())->toBe('quan-tri-7f3k')
        ->and(AuditLog::query()->where('action', 'identity.admin_path.changed')->first()?->changes)->toMatchArray(['from' => 'admin', 'to' => 'quan-tri-7f3k']);

    // Lệnh cứu hộ trên máy chủ: về mặc định.
    $this->artisan('vani:admin-path', ['--reset' => true])->assertSuccessful();
    expect(app(Settings::class)->get('core', AdminPathSetting::SETTING))->toBeNull();
    $this->artisan('vani:admin-path', ['path' => 'api'])->assertFailed();
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

    // Trang có phiên của storefront (trang công khai như trang chủ chạy không phiên — storefront §5).
    $storefront = $this->get('/gio-hang');
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
