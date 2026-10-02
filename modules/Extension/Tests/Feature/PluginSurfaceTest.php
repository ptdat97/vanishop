<?php

use Illuminate\Support\Facades\File;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginLoader;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Data\HealthStatus;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\PluginHealthCheck;
use Modules\Extension\Domain\Plugin\InvalidManifest;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\PluginServiceProvider;
use Modules\Extension\Tests\Fixtures\ContributingPluginProvider;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

it('manifest: kind phải thuộc danh sách chuẩn', function () {
    PluginManifest::fromArray(['id' => 'acme.x', 'name' => 'X', 'version' => '1.0.0', 'kind' => 'business', 'provider' => 'X', 'requires' => ['vanishop' => '^0.3']], '/tmp/x');
})->throws(InvalidManifest::class, 'kind [business] không hợp lệ');

it('publishHooks: chỉ nhận hook bắt đầu bằng id plugin', function () {
    $provider = new class(app()) extends PluginServiceProvider
    {
        protected function pluginId(): string
        {
            return 'acme.loyalty';
        }

        public function publish(string $file): void
        {
            $this->publishHooks($file);
        }
    };
    $file = storage_path('framework/testing/hooks-'.uniqid().'.php');
    File::put($file, "<?php return ['acme.loyalty.points.earned' => ['type' => 'action', 'visibility' => 'public', 'since' => '1.0', 'args' => [], 'description' => 'x']];");
    $provider->publish($file);
    expect(app(HookRegistry::class)->get('acme.loyalty.points.earned'))->not->toBeNull();

    File::put($file, "<?php return ['vani.order.after_create2' => ['type' => 'action', 'visibility' => 'public', 'since' => '1.0', 'args' => [], 'description' => 'x']];");
    expect(fn () => $provider->publish($file))->toThrow(InvalidArgumentException::class, 'phải bắt đầu bằng "acme.loyalty."');
    File::delete($file);
});

it('doctor: plugin khai loại gắn extension point mà không đóng góp → kind_mismatch; health lỗi/cảnh báo được báo', function () {
    $root = FixturePlugins::install(['Gateway' => ['id' => 'fixture.gateway', 'kind' => 'payment_gateway', 'provider' => ContributingPluginProvider::class]]);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    app(PluginManager::class)->install('fixture.gateway');
    app(PluginManager::class)->enable('fixture.gateway');
    app()->forgetInstance(PluginLoader::class); // loader đọc cache của thư mục plugin giả
    app(PluginLoader::class)->load();

    $check = new class implements PluginHealthCheck
    {
        public function check(): HealthStatus
        {
            return HealthStatus::warning('Token sắp hết hạn');
        }
    };
    app()->instance($check::class, $check);
    app(Extensions::class)->contribute(PluginHealthCheck::TAG, $check::class, 'fixture.gateway');

    $issues = collect(app(PluginDoctor::class)->diagnose())->where('plugin', 'fixture.gateway')->pluck('message', 'code')->all();

    expect($issues)->toHaveKey('kind_mismatch')
        ->and($issues['health_warning'] ?? null)->toBe('Token sắp hết hạn');
    File::deleteDirectory($root);
});
