<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

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
                && collect($plugins)->where('bundled', true)->pluck('id')->sort()->values()->all() === ['vani.bank-transfer', 'vani.cod', 'vani.phone-vn', 'vani.provinces-vn', 'vani.shipping-flat-rate', 'vani.tax-vn-vat']));
});

it('cần quyền extension.plugins.view', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin/plugins')->assertForbidden();
});

/*
| Admin → Plugin (plugin-system §5b): danh sách có mô tả/icon, chi tiết, thao tác vòng đời qua PluginManager, cấu hình riêng.
*/
describe('quản lý plugin trên Admin', function () {
    beforeEach(function () {
        $this->root = FixturePlugins::install([
            'Greeting' => ['id' => 'fixture.greeting', 'description' => ['vi' => 'Lời chào cho khách', 'en' => 'Greets customers'], 'author' => 'Owner', 'icon' => 'icon.svg'],
            'Base' => ['id' => 'fixture.base'],
            'Child' => ['id' => 'fixture.child', 'requires' => ['vanishop' => '^0.3', 'plugins' => ['fixture.base' => '^1.0']]],
            'Future' => ['id' => 'fixture.future', 'requires' => ['vanishop' => '^9.0']],
            'BadIcon' => ['id' => 'fixture.bad-icon', 'icon' => '../../.env'],
        ]);
        File::put("{$this->root}/Greeting/icon.svg", '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $this->manager = StaffUser::factory()->withPermissions(['admin.access', 'extension.plugins.view', 'extension.plugins.manage', 'settings.manage'])->create();
        $this->actingAs($this->manager, 'staff');
    });

    afterEach(function () {
        File::deleteDirectory($this->root);
    });

    it('danh sách: mô tả theo ngôn ngữ, tác giả, icon; manifest có icon ra ngoài thư mục plugin bị loại', function () {
        $this->get('/admin/plugins')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('plugins.3.id', 'fixture.greeting')
            ->where('plugins.3.description', 'Lời chào cho khách')
            ->where('plugins.3.author', 'Owner')
            ->where('plugins.3.has_icon', true)
            ->where('plugins.3.status', 'discovered')
            ->where('invalid', fn ($invalid) => str_contains((string) collect($invalid)->first(), 'icon')));

        $this->get('/admin/plugins/fixture.greeting/icon')->assertOk()
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
        $this->get('/admin/plugins/fixture.base/icon')->assertNotFound();
    });

    it('chi tiết: phụ thuộc, plugin phụ thuộc ngược, lý do không cài được, thao tác theo trạng thái', function () {
        $this->get('/admin/plugins/fixture.future')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Extension::Plugins/Show')
            ->where('plugin.actions', ['install'])
            ->where('plugin.problems', fn ($problems) => collect($problems)->contains(fn (string $message): bool => str_contains($message, '^9.0'))));

        app(PluginManager::class)->install('fixture.base');
        app(PluginManager::class)->enable('fixture.base');
        app(PluginManager::class)->install('fixture.child');

        $this->get('/admin/plugins/fixture.base')->assertInertia(fn (Assert $page) => $page
            ->where('plugin.dependents', ['fixture.child'])->where('plugin.actions', ['disable'])->where('canManage', true));
        $this->get('/admin/plugins/fixture.child')->assertInertia(fn (Assert $page) => $page
            ->where('plugin.requires_plugins.0', ['id' => 'fixture.base', 'constraint' => '^1.0', 'name' => 'fixture.base', 'status' => 'enabled'])
            ->where('plugin.actions', ['enable', 'uninstall']));
        $this->get('/admin/plugins/khong.ton-tai')->assertNotFound();
    });

    it('cài → bật → tắt → gỡ từ Admin, cùng audit với CLI', function () {
        $this->post('/admin/plugins/fixture.greeting/install')->assertSessionHas('success');
        $this->post('/admin/plugins/fixture.greeting/enable')->assertSessionHas('success');
        expect(PluginRecord::query()->find('fixture.greeting')->status)->toBe(PluginStatus::Enabled);

        $this->post('/admin/plugins/fixture.greeting/disable', ['mode' => 'normal'])->assertSessionHas('success');
        $this->post('/admin/plugins/fixture.greeting/uninstall', ['purge' => false])->assertRedirect('/admin/plugins');

        expect(PluginRecord::query()->find('fixture.greeting'))->toBeNull()
            ->and(AuditLog::query()->where('subject_id', 'fixture.greeting')->pluck('action')->all())
            ->toBe(['extension.plugin.installed', 'extension.plugin.enabled', 'extension.plugin.disabled', 'extension.plugin.uninstalled']);
    });

    it('lỗi của PluginManager hiện thành lỗi nghiệp vụ; không tắt plugin còn plugin khác phụ thuộc khi gỡ', function () {
        $this->post('/admin/plugins/fixture.child/install')->assertSessionHasErrors('business');
        $this->post('/admin/plugins/fixture.future/install')->assertSessionHasErrors('business');
        expect(session('errors')->first('business'))->toContain('^9.0');

        app(PluginManager::class)->install('fixture.base');
        app(PluginManager::class)->install('fixture.child');
        $this->post('/admin/plugins/fixture.base/uninstall')->assertSessionHasErrors('business');
        expect(PluginRecord::query()->find('fixture.base'))->not->toBeNull();
    });

    it('tắt ngay phải tick xác nhận; xoá dữ liệu phải gõ lại id', function () {
        FixturePlugins::withMigration($this->root, 'Greeting');
        $this->post('/admin/plugins/fixture.greeting/install');
        $this->post('/admin/plugins/fixture.greeting/enable');
        expect(Schema::hasTable('plg_fixture_items'))->toBeTrue();

        $this->post('/admin/plugins/fixture.greeting/disable', ['mode' => 'force'])->assertSessionHasErrors('confirm_force');
        $this->post('/admin/plugins/fixture.greeting/disable', ['mode' => 'force', 'confirm_force' => true])->assertSessionHasNoErrors();

        $this->post('/admin/plugins/fixture.greeting/uninstall', ['purge' => true, 'confirm_id' => 'fixture.sai'])->assertSessionHasErrors('confirm_id');
        expect(Schema::hasTable('plg_fixture_items'))->toBeTrue();
        $this->post('/admin/plugins/fixture.greeting/uninstall', ['purge' => true, 'confirm_id' => 'fixture.greeting'])->assertSessionHasNoErrors();
        expect(Schema::hasTable('plg_fixture_items'))->toBeFalse();
    });

    it('nâng cấp khi manifest có version mới hơn', function () {
        app(PluginManager::class)->install('fixture.base');
        $manifest = json_decode(File::get("{$this->root}/Base/vanishop.json"), true);
        File::put("{$this->root}/Base/vanishop.json", json_encode(['version' => '1.1.0'] + $manifest));
        app()->instance(ManifestRepository::class, new ManifestRepository($this->root));

        $this->get('/admin/plugins/fixture.base')->assertInertia(fn (Assert $page) => $page->where('plugin.upgrade_available', true)->where('plugin.actions', ['enable', 'uninstall', 'upgrade']));
        $this->post('/admin/plugins/fixture.base/upgrade')->assertSessionHas('success');
        expect(PluginRecord::query()->find('fixture.base')->version)->toBe('1.1.0');
    });

    it('chỉ có quyền xem: không thấy/không chạy được thao tác', function () {
        $viewer = StaffUser::factory()->withPermissions(['admin.access', 'extension.plugins.view'])->create();

        $this->actingAs($viewer, 'staff')->get('/admin/plugins/fixture.greeting')->assertInertia(fn (Assert $page) => $page->where('canManage', false)->where('canConfigure', false));
        $this->post('/admin/plugins/fixture.greeting/install')->assertForbidden();
        expect(PluginRecord::query()->find('fixture.greeting'))->toBeNull();
    });

    it('trang cấu hình riêng của plugin: hiện và lưu cấu hình theo namespace plugin; plugin không có cấu hình → 404', function () {
        app(Settings::class)->define(new SettingDefinition('fixture.greeting', 'greeting', 'Lời chào', default: 'xin chào'));

        $this->get('/admin/plugins')->assertInertia(fn (Assert $page) => $page->where('plugins.3.has_settings', true));
        $this->get('/admin/plugins/fixture.greeting/settings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Extension::Plugins/Settings')->where('fields.0.key', 'greeting')->where('fields.0.effective', 'xin chào'));
        $this->put('/admin/plugins/fixture.greeting/settings', ['values' => ['greeting' => 'chào bạn']])->assertSessionHas('success');

        expect(app(Settings::class)->get('fixture.greeting', 'greeting'))->toBe('chào bạn');
        $this->get('/admin/plugins/fixture.base/settings')->assertNotFound();
    });
});
