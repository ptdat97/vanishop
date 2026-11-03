<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

/*
| plugin-system §7: cài/nâng cấp plugin tự chạy migration rồi seeder (`Database/Seeders/`), mỗi seeder một lần.
*/

beforeEach(function () {
    $this->root = FixturePlugins::install(['Greeting' => ['id' => 'fixture.greeting']]);
    FixturePlugins::withMigration($this->root, 'Greeting');
    FixturePlugins::withSeeder($this->root, 'Greeting', 'ItemsSeeder', <<<'PHP'
        if (config('testing.seed_fail')) { throw new \RuntimeException('dữ liệu hỏng'); }
                \Illuminate\Support\Facades\DB::table('plg_fixture_items')->insert(['id' => (int) \Illuminate\Support\Facades\DB::table('plg_fixture_items')->max('id') + 1]);
        PHP);
    $this->plugins = app(PluginManager::class);
    $this->items = fn (): int => DB::table('plg_fixture_items')->count();
    app(CurrentContext::class)->set(ContextScope::system('test'));
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

it('cài: chạy migration rồi seeder, ghi nhận seeder đã chạy, audit; doctor không báo seeder chờ', function () {
    $this->plugins->install('fixture.greeting');

    expect(($this->items)())->toBe(1)
        ->and(DB::table('plugin_seeders')->where('plugin_id', 'fixture.greeting')->pluck('seeder')->all())->toBe(['Modules\\Extension\\Tests\\Fixtures\\Database\\Seeders\\ItemsSeeder'])
        ->and(AuditLog::query()->where('action', 'extension.plugin.installed')->sole()->changes)->toHaveKey('seeded')
        ->and(collect(app(PluginDoctor::class)->diagnose())->where('code', 'seeders_pending')->all())->toBe([]);
});

it('gỡ giữ dữ liệu rồi cài lại: không seed lần hai; gỡ --purge rồi cài lại: seed lại từ đầu', function () {
    $this->plugins->install('fixture.greeting');
    $this->plugins->uninstall('fixture.greeting');
    $this->plugins->install('fixture.greeting');
    expect(($this->items)())->toBe(1);

    $this->plugins->uninstall('fixture.greeting', purge: true);
    expect(DB::table('plugin_seeders')->count())->toBe(0);
    $this->plugins->install('fixture.greeting');
    expect(($this->items)())->toBe(1);
});

it('seeder lỗi: dữ liệu của seeder đó không ghi (transaction), plugin failed; sửa xong vani:plugin:seed → installed', function () {
    config(['testing.seed_fail' => true]);

    expect(fn () => $this->plugins->install('fixture.greeting'))->toThrow(PluginOperationFailed::class, 'dữ liệu hỏng');
    expect(PluginRecord::query()->find('fixture.greeting')->status)->toBe(PluginStatus::Failed)
        ->and(($this->items)())->toBe(0)
        ->and(collect(app(PluginDoctor::class)->diagnose())->pluck('code')->all())->toContain('seeders_pending');

    config(['testing.seed_fail' => false]);
    $this->artisan('vani:plugin:seed', ['plugin' => 'fixture.greeting'])->expectsOutputToContain('ItemsSeeder')->assertSuccessful();

    expect(PluginRecord::query()->find('fixture.greeting')->status)->toBe(PluginStatus::Installed)
        ->and(($this->items)())->toBe(1);
    $this->artisan('vani:plugin:seed', ['plugin' => 'fixture.greeting'])->expectsOutput('Không có seeder nào cần chạy.')->assertSuccessful();
});

it('nâng cấp: chỉ chạy seeder mới thêm; --rerun chạy lại tất cả', function () {
    $this->plugins->install('fixture.greeting');

    FixturePlugins::withSeeder($this->root, 'Greeting', 'MoreItemsSeeder', "\\Illuminate\\Support\\Facades\\DB::table('plg_fixture_items')->insert(['id' => (int) \\Illuminate\\Support\\Facades\\DB::table('plg_fixture_items')->max('id') + 1]);");
    $manifest = json_decode(File::get("{$this->root}/Greeting/vanishop.json"), true);
    File::put("{$this->root}/Greeting/vanishop.json", json_encode(['version' => '1.1.0'] + $manifest));
    app()->instance(ManifestRepository::class, new ManifestRepository($this->root));

    app(PluginManager::class)->upgrade('fixture.greeting');
    expect(($this->items)())->toBe(2);

    $this->artisan('vani:plugin:seed', ['plugin' => 'fixture.greeting', '--rerun' => true])->assertSuccessful();
    expect(($this->items)())->toBe(4);
});

it('file trong Database/Seeders không phải Seeder → cài thất bại, báo rõ', function () {
    FixturePlugins::withSeeder($this->root, 'Greeting', 'NotASeeder', '', parent: '\\stdClass');

    expect(fn () => $this->plugins->install('fixture.greeting'))->toThrow(PluginOperationFailed::class, 'không kế thừa');
});
