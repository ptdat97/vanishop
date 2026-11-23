<?php

use App\Observability\HealthCheck;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Modules\Extension\Persistence\Models\PluginRecord;

beforeEach(function () {
    $this->useArrayCache();
    app()->detectEnvironment(fn () => 'production');
    config([
        'app.debug' => false, 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'app.url' => 'https://shop.example',
        'session.driver' => 'array', 'session.secure' => true, 'queue.default' => 'sync',
        'vanishop.admin.path' => 'quan-tri-x9', 'vanishop.plugins.safe_mode' => false, 'vanishop.customer.otp.log_sender' => false,
        'vanishop.load_test.bypass_ips' => [], 'mail.mailers.vani.fallback' => 'smtp',
    ]);
    app()->usePublicPath(Storage::fake('local')->path('release'));
    File::ensureDirectoryExists(public_path('build/assets'));
    File::put(public_path('build/assets/entry.js'), '/* build */');
    File::put(public_path('build/manifest.json'), json_encode(array_fill_keys(
        ['resources/js/admin.ts', 'custom/theme/vani-base/css/app.css', 'custom/theme/vani-base/js/app.js'],
        ['file' => 'assets/entry.js'],
    ), JSON_THROW_ON_ERROR));
});

it('kiểm tra release trước cutover và xuất JSON không cần worker đang chạy', function () {
    expect(Artisan::call('vani:production:check', ['--json' => true]))->toBe(0);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($report['checks']['security']['status'])->toBe('ok')
        ->and($report['checks']['assets']['status'])->toBe('ok')
        ->and($report['checks']['database']['status'])->toBe('ok')
        ->and($report['checks'])->not->toHaveKey('horizon')
        ->and($report['checks'])->not->toHaveKey('scheduler');
});

it('chặn release thiếu asset, bật debug hoặc có plugin chưa nâng', function () {
    File::delete(public_path('build/assets/entry.js'));
    config(['app.debug' => true]);
    PluginRecord::query()->whereKey('vani.cod')->update(['version' => '0.0.1']);

    expect(Artisan::call('vani:production:check', ['--json' => true]))->toBe(1);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($report['status'])->toBe('fail')
        ->and($report['checks']['assets']['detail'])->toContain('Artifact thiếu file')
        ->and($report['checks']['security']['status'])->toBe('fail')
        ->and($report['checks']['plugins']['detail'])->toContain('vani.cod/upgrade_pending');
});

it('chặn release còn dùng file hot của Vite', function () {
    File::put(public_path('hot'), 'http://localhost:5173');

    expect(Artisan::call('vani:production:check', ['--json' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('Release có file hot của Vite.');
});

it('runtime yêu cầu scheduler và Horizon đang hoạt động', function () {
    config(['queue.default' => 'redis']);
    $connection = Mockery::mock();
    $connection->shouldReceive('command')->with('info', ['memory'])->andReturn(['maxmemory_policy' => 'noeviction']);
    Redis::shouldReceive('connection')->with('default')->andReturn($connection);

    $repository = Mockery::mock(MasterSupervisorRepository::class);
    $repository->shouldReceive('all')->andReturn([], [(object) ['status' => 'running']]);
    app()->instance(MasterSupervisorRepository::class, $repository);
    $run = function (int $exitCode): array {
        expect(Artisan::call('vani:production:check', ['--runtime' => true, '--json' => true]))->toBe($exitCode);

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    };

    $offline = $run(1);
    expect($offline['checks']['scheduler']['status'])->toBe('fail')
        ->and($offline['checks']['horizon']['status'])->toBe('fail');

    Cache::put(HealthCheck::HEARTBEAT_KEY, time(), 60);
    $online = $run(0);
    expect($online['checks']['scheduler']['status'])->toBe('ok')
        ->and($online['checks']['horizon']['status'])->toBe('ok');
});
