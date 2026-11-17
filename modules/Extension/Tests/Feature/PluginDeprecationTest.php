<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookType;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

/*
| Củng cố kiến trúc K3 (P5): plugin còn dùng API đã ngừng khuyến nghị — doctor cảnh báo (quét tĩnh), nghe hook deprecated
| ghi log `vani.deprecation` (một lần mỗi tiến trình).
*/

beforeEach(function () {
    app(HookRegistry::class)->declare(new HookDefinition('vani.test.retired_hook', HookType::Filter, true, '0.1', deprecated: 'dùng vani.test.new_hook'));
    $this->root = FixturePlugins::install(['Greeting' => ['id' => 'fixture.greeting']]);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    File::put("{$this->root}/Greeting/Uses.php", <<<'PHP'
<?php

use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Tests\Fixtures\RetiredApi;

$zone = ReportPeriod::TIMEZONE;
$resources = AdminScreen::RESOURCES;
$period = ReportPeriod::fromPreset('today');
$old = RetiredApi::VERSION;
$hook = 'vani.test.retired_hook';
PHP);
    app(PluginManager::class)->install('fixture.greeting');
});

afterEach(fn () => File::deleteDirectory($this->root));

it('doctor cảnh báo từng chỗ dùng: hằng #[\Deprecated], class #[PluginApi(deprecated)], hook deprecated; method còn dùng được không báo', function () {
    $usages = collect(app(PluginDoctor::class)->diagnose())->where('code', 'deprecated_usage')->where('plugin', 'fixture.greeting')->pluck('message')->all();

    expect($usages)->toHaveCount(4)
        ->and($usages[0])->toContain('Modules\Extension\Contracts\Data\ReportPeriod::TIMEZONE')->toContain('vanishop.locale.timezone')->toContain('Uses.php:7')
        ->and($usages[1])->toContain('Modules\Extension\Contracts\AdminScreen::RESOURCES')->toContain('từ 0.3.39')
        ->and($usages[2])->toContain('Modules\Extension\Tests\Fixtures\RetiredApi')->toContain('dùng RetiredApi2')
        ->and($usages[3])->toContain('hook vani.test.retired_hook')->toContain('dùng vani.test.new_hook');
});

it('plugin nghe hook deprecated → log vani.deprecation một lần; Core nghe thì không log', function () {
    Log::spy();
    $hooks = app(HookManager::class);
    $hooks->onFilter('vani.test.retired_hook', fn (mixed $value): mixed => $value, pluginId: 'fixture.greeting');
    $hooks->onFilter('vani.test.retired_hook', fn (mixed $value): mixed => $value, pluginId: 'fixture.greeting');
    $hooks->onFilter('vani.test.retired_hook', fn (mixed $value): mixed => $value);

    Log::shouldHaveReceived('warning')->once()->with('vani.deprecation', ['kind' => 'hook', 'feature' => 'vani.test.retired_hook', 'plugin' => 'fixture.greeting', 'message' => 'dùng vani.test.new_hook']);
});
