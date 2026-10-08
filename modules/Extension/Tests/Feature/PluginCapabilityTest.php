<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Plugin\InvalidManifest;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

/*
| Capability giữa plugin (roadmap Phase 5, 0.3.33): `requires.capabilities` = tag cần ít nhất một implementation đang bật,
| không phụ thuộc vào plugin cụ thể nào.
*/

final class FixtureWidget {}

final class FixtureWidgetA {}

final class FixtureWidgetB {}

beforeEach(function () {
    $this->root = FixturePlugins::install([
        'Shop' => ['id' => 'fixture.shop', 'requires' => ['vanishop' => '^0.3', 'capabilities' => ['fixture.widgets']]],
        'WidgetA' => ['id' => 'fixture.widgets-a'],
        'WidgetB' => ['id' => 'fixture.widgets-b'],
    ]);
    $extensions = app(Extensions::class);
    $extensions->contribute('fixture.widgets', FixtureWidgetA::class, 'fixture.widgets-a');
    $extensions->contribute('fixture.widgets', FixtureWidgetB::class, 'fixture.widgets-b');
    $this->plugins = app(PluginManager::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    foreach (['fixture.shop', 'fixture.widgets-a', 'fixture.widgets-b'] as $id) {
        $this->plugins->install($id);
    }
    $this->fails = function (Closure $action, string $contains): void {
        try {
            $action();
            $this->fail('Đáng lẽ phải bị chặn.');
        } catch (PluginOperationFailed $exception) {
            expect($exception->getMessage().' '.implode(' ', array_map(fn ($problem) => $problem->code.' '.$problem->message, $exception->problems)))->toContain($contains);
        }
    };
});

afterEach(fn () => File::deleteDirectory($this->root));

it('manifest: requires.capabilities là danh sách tag hợp lệ', function () {
    $base = ['id' => 'vendor.x', 'name' => 'X', 'version' => '1.0.0', 'kind' => 'feature', 'provider' => 'X', 'requires' => ['vanishop' => '^0.3']];

    expect(PluginManifest::fromArray($base, '/x')->requiresCapabilities)->toBe([])
        ->and(PluginManifest::fromArray(['requires' => ['vanishop' => '^0.3', 'capabilities' => ['vani.payment.gateways', 'vani.payment.gateways']]] + $base, '/x')->requiresCapabilities)->toBe(['vani.payment.gateways']);
    expect(fn () => PluginManifest::fromArray(['requires' => ['vanishop' => '^0.3', 'capabilities' => ['Payment Gateways']]] + $base, '/x'))->toThrow(InvalidManifest::class);
});

it('không bật được khi chưa có nguồn capability; có một plugin cung cấp bất kỳ đang bật → bật được', function () {
    ($this->fails)(fn () => $this->plugins->enable('fixture.shop'), 'missing_capability');

    $this->plugins->enable('fixture.widgets-b');
    $this->plugins->enable('fixture.shop');

    expect(DB::table('plugins')->where('id', 'fixture.shop')->value('status'))->toBe('enabled');
});

it('không tắt được nguồn cuối cùng mà plugin đang chạy cần; có nguồn thay thế (plugin khác hoặc Core) → tắt được', function () {
    $this->plugins->enable('fixture.widgets-a');
    $this->plugins->enable('fixture.shop');

    ($this->fails)(fn () => $this->plugins->disable('fixture.widgets-a'), 'fixture.shop cần [fixture.widgets]');

    $this->plugins->enable('fixture.widgets-b');
    $this->plugins->disable('fixture.widgets-a');
    ($this->fails)(fn () => $this->plugins->disable('fixture.widgets-b'), 'nguồn cuối cùng');

    // Core có implementation mặc định → mọi plugin cung cấp đều tắt được.
    app(Extensions::class)->tag([FixtureWidget::class], 'fixture.widgets');
    $this->plugins->disable('fixture.widgets-b');

    // Plugin cần đã tắt → không còn chặn ai.
    $this->plugins->disable('fixture.shop');
    expect(DB::table('plugins')->where('status', 'enabled')->count())->toBe(0);
});

it('nguồn đang draining không được tính là thay thế (không nhận giao dịch mới)', function () {
    $this->plugins->enable('fixture.widgets-a');
    $this->plugins->enable('fixture.widgets-b');
    $this->plugins->enable('fixture.shop');
    DB::table('plugins')->where('id', 'fixture.widgets-b')->update(['status' => 'draining']);

    ($this->fails)(fn () => $this->plugins->disable('fixture.widgets-a'), 'fixture.shop cần [fixture.widgets]');
});

it('doctor báo capability_missing khi plugin đang chạy không còn nguồn (vd. nguồn bị tắt ngoài luồng)', function () {
    $this->plugins->enable('fixture.widgets-a');
    $this->plugins->enable('fixture.shop');
    expect(collect(app(PluginDoctor::class)->diagnose())->where('code', 'capability_missing'))->toBeEmpty();

    DB::table('plugins')->where('id', 'fixture.widgets-a')->update(['status' => 'disabled']);
    $issue = collect(app(PluginDoctor::class)->diagnose())->firstWhere('code', 'capability_missing');
    expect($issue)->toMatchArray(['plugin' => 'fixture.shop', 'level' => 'error'])->and($issue['message'])->toContain('fixture.widgets');
});
