<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

beforeEach(function () {
    $this->root = FixturePlugins::install([
        'Greeting' => ['id' => 'fixture.greeting'],
        'Future' => ['id' => 'fixture.future', 'requires' => ['vanishop' => '^9.0']],
    ]);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    $this->plugins = fn (): PluginManager => app(PluginManager::class);
    // Giả lập deploy code mới của plugin: sửa manifest trên đĩa rồi nạp lại danh sách manifest.
    $this->deploy = function (string $directory, array $changes): void {
        $path = "{$this->root}/{$directory}/vanishop.json";
        File::put($path, json_encode([...json_decode(File::get($path), true), ...$changes], JSON_PRETTY_PRINT));
        app()->instance(ManifestRepository::class, new ManifestRepository($this->root));
    };
    ($this->plugins)()->install('fixture.greeting');
});

afterEach(fn () => File::deleteDirectory($this->root));

it('upgrade: chạy migration mới, cập nhật version, có audit; cùng version hoặc hạ version bị từ chối', function () {
    expect(fn () => ($this->plugins)()->upgrade('fixture.greeting'))->toThrow(PluginOperationFailed::class, 'đã ở version 1.0.0');

    FixturePlugins::withMigration($this->root, 'Greeting');
    ($this->deploy)('Greeting', ['version' => '1.1.0']);

    $this->artisan('vani:plugin:upgrade', ['plugin' => 'fixture.greeting'])->expectsOutputToContain('1.1.0')->assertSuccessful();
    expect(PluginRecord::query()->find('fixture.greeting')->version)->toBe('1.1.0')
        ->and(Schema::hasTable('plg_fixture_items'))->toBeTrue()
        ->and(AuditLog::query()->where('action', 'extension.plugin.upgraded')->value('changes'))->toMatchArray(['from' => '1.0.0', 'to' => '1.1.0']);

    ($this->deploy)('Greeting', ['version' => '1.0.5']);
    expect(fn () => ($this->plugins)()->upgrade('fixture.greeting'))->toThrow(PluginOperationFailed::class, 'Không hạ version');
});

it('upgrade: bản mới không tương thích Core bị từ chối; plugin failed nâng thành công về installed', function () {
    ($this->deploy)('Greeting', ['version' => '2.0.0', 'requires' => ['vanishop' => '^9.0']]);
    expect(fn () => ($this->plugins)()->upgrade('fixture.greeting'))->toThrow(PluginOperationFailed::class, 'incompatible_core');

    ($this->plugins)()->markFailed('fixture.greeting', 'incompatible_core');
    ($this->deploy)('Greeting', ['version' => '2.0.1', 'requires' => ['vanishop' => '^0.3']]);
    expect(($this->plugins)()->upgrade('fixture.greeting')->status)->toBe(PluginStatus::Installed);
});

it('doctor: báo version cần nâng, migration chưa chạy, plugin failed, manifest mất, plugin chưa cài không tương thích', function () {
    // Thư mục plugin giả không có plugin hệ thống: coi các extension point bắt buộc đã có implementation của Core,
    // để doctor chỉ báo vấn đề của plugin giả (thiếu implementation bắt buộc: InstallCommandTest).
    foreach (array_keys(app(Extensions::class)->requirements()) as $tag) {
        app(Extensions::class)->tag([stdClass::class], $tag);
    }

    expect(app(PluginDoctor::class)->diagnose())->toBe([
        ['plugin' => 'fixture.future', 'level' => 'warning', 'code' => 'incompatible_core', 'message' => 'Chưa cài; cần VaniShop ^9.0, hiện tại 0.3.4.'],
    ]);
    $this->artisan('vani:plugin:doctor')->assertSuccessful(); // chỉ cảnh báo → mã 0

    FixturePlugins::withMigration($this->root, 'Greeting');
    ($this->deploy)('Greeting', ['version' => '1.2.0']);
    PluginRecord::query()->create(['id' => 'fixture.gone', 'version' => '1.0.0', 'status' => PluginStatus::Installed, 'installed_at' => now()]);
    ($this->plugins)()->markFailed('fixture.greeting', 'boom');

    $codes = collect(app(PluginDoctor::class)->diagnose())->map(fn (array $issue): string => "{$issue['plugin']}:{$issue['code']}")->all();
    expect($codes)->toContain('fixture.greeting:failed', 'fixture.greeting:upgrade_pending', 'fixture.greeting:migrations_pending', 'fixture.gone:manifest_missing');
    $this->artisan('vani:plugin:doctor')->assertFailed();
    expect(Artisan::call('vani:plugin:doctor', ['--json' => true]))->toBe(1)
        ->and(json_decode(Artisan::output(), true))->toBeArray();
});
