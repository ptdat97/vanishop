<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Plugin\InvalidManifest;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

/*
| Khai báo dữ liệu plugin (roadmap Phase 5, 0.3.24): owned / references / retained; gỡ + purge an toàn.
*/

beforeEach(function () {
    $this->root = FixturePlugins::install([
        'Base' => ['id' => 'fixture.base', 'data' => ['owned' => ['plg_fixture_items'], 'retained' => []]],
        'Child' => ['id' => 'fixture.child', 'data' => ['owned' => ['plg_fixture_child'], 'references' => ['plg_fixture_child.item_id' => 'plg_fixture_items.id']]],
        'Ledger' => ['id' => 'fixture.ledger', 'data' => ['owned' => ['plg_fixture_items'], 'retained' => ['plg_fixture_items']]],
        'Bare' => ['id' => 'fixture.bare'],
    ]);
    $this->plugins = app(PluginManager::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));
});

afterEach(function () {
    Schema::dropIfExists('test_external_refs');
    Schema::dropIfExists('plg_fixture_items');
    File::deleteDirectory($this->root);
});

it('manifest: owned phải là plg_*, retained ⊆ owned, references bắt đầu từ bảng owned', function (array $data, string $message) {
    expect(fn () => PluginManifest::fromArray(['id' => 'x.y', 'name' => 'x', 'version' => '1.0.0', 'kind' => 'feature', 'provider' => 'X', 'requires' => ['vanishop' => '^0.3'], 'data' => $data], '/tmp/x'))
        ->toThrow(InvalidManifest::class, $message);
})->with([
    'owned sai tiền tố' => [['owned' => ['orders']], 'plg_*'],
    'retained ngoài owned' => [['owned' => ['plg_a'], 'retained' => ['plg_b']], 'data.owned'],
    'reference từ bảng lạ' => [['owned' => ['plg_a'], 'references' => ['orders.id' => 'payments.id']], 'data.owned'],
    'reference sai dạng' => [['owned' => ['plg_a'], 'references' => ['plg_a.x' => 'payments']], 'bang.cot'],
]);

it('purge bị chặn khi plugin khác (đang cài) khai báo tham chiếu tới bảng của plugin', function () {
    FixturePlugins::withMigration($this->root, 'Base');
    $this->plugins->install('fixture.base');
    $this->plugins->install('fixture.child');

    expect(fn () => $this->plugins->uninstall('fixture.base', purge: true))->toThrow(PluginOperationFailed::class, 'plugin fixture.child tham chiếu plg_fixture_items.id');
    expect(Schema::hasTable('plg_fixture_items'))->toBeTrue();

    $this->plugins->uninstall('fixture.child');
    $this->plugins->uninstall('fixture.base', purge: true);
    expect(Schema::hasTable('plg_fixture_items'))->toBeFalse();
});

it('purge bị chặn khi có khoá ngoại thật từ bảng ngoài plugin; gỡ không purge vẫn được (giữ dữ liệu)', function () {
    FixturePlugins::withMigration($this->root, 'Base');
    $this->plugins->install('fixture.base');
    Schema::create('test_external_refs', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('item_id')->constrained('plg_fixture_items');
    });

    expect(fn () => $this->plugins->uninstall('fixture.base', purge: true))->toThrow(PluginOperationFailed::class, 'khoá ngoại test_external_refs(item_id) → plg_fixture_items');

    $this->plugins->uninstall('fixture.base');
    expect(Schema::hasTable('plg_fixture_items'))->toBeTrue()->and(PluginRecord::query()->find('fixture.base'))->toBeNull();
});

it('dữ liệu retained: purge cần --drop-retained; CLI hỏi xác nhận', function () {
    FixturePlugins::withMigration($this->root, 'Ledger');
    $this->plugins->install('fixture.ledger');

    $this->artisan('vani:plugin:uninstall', ['plugin' => 'fixture.ledger', '--purge' => true])->expectsOutputToContain('dữ liệu phải lưu giữ: plg_fixture_items')->assertFailed();
    $this->artisan('vani:plugin:uninstall', ['plugin' => 'fixture.ledger', '--purge' => true, '--drop-retained' => true])
        ->expectsConfirmation('Xoá cả dữ liệu phải lưu giữ (data.retained)? Không khôi phục được.', 'no')->assertFailed();
    expect(Schema::hasTable('plg_fixture_items'))->toBeTrue();

    $this->artisan('vani:plugin:uninstall', ['plugin' => 'fixture.ledger', '--purge' => true, '--drop-retained' => true, '--yes' => true])->assertSuccessful();
    expect(Schema::hasTable('plg_fixture_items'))->toBeFalse();
});

it('gỡ (kể cả không purge) bị chặn khi implementation của plugin còn việc dở dang', function () {
    $this->plugins->install('fixture.base');
    app(Extensions::class)->contribute('test.disposable', stdClass::class, 'fixture.base');
    app(Extensions::class)->guardDisable('test.disposable', fn (object $implementation): string => 'còn 2 giao dịch đang chờ');

    expect(fn () => $this->plugins->uninstall('fixture.base'))->toThrow(PluginOperationFailed::class, 'còn 2 giao dịch đang chờ');
});

it('doctor: có migration mà chưa khai data.owned; bảng khai báo không tồn tại', function () {
    FixturePlugins::withMigration($this->root, 'Bare');
    FixturePlugins::withMigration($this->root, 'Child');
    $this->plugins->install('fixture.bare');
    $this->plugins->install('fixture.child'); // migration tạo plg_fixture_items, khai báo plg_fixture_child

    $codes = collect(app(PluginDoctor::class)->diagnose())->map(fn (array $issue): string => $issue['plugin'].':'.$issue['code'])->all();
    expect($codes)->toContain('fixture.bare:data_undeclared')->toContain('fixture.child:data_owned_missing');
});
